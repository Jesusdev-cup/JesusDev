// Package entries — the domain. It lives in internal/, so the compiler
// itself prevents imports from outside the module: the boundary is
// enforced by the toolchain, not by discipline.
package entries

import (
	"sync"
	"time"
)

// Entry is the contract's resource shape.
type Entry struct {
	ID        string    `json:"id"`
	Task      string    `json:"task"`
	Minutes   int       `json:"minutes"`
	CreatedAt time.Time `json:"created_at"`
}

// NewEntry is the validated payload — the schema of the contract.
type NewEntry struct {
	Task    string `json:"task"`
	Minutes int    `json:"minutes"`
}

// Store is the domain's public API surface: consumers depend on this
// interface, not on the in-memory implementation.
type Store interface {
	List(day string) []Entry
	Create(e NewEntry) Entry
}

// MemStore is a reference in-memory implementation.
type MemStore struct {
	mu   sync.Mutex
	rows map[string]Entry
}

func NewMemStore() *MemStore {
	return &MemStore{rows: make(map[string]Entry)}
}

func (s *MemStore) List(day string) []Entry {
	s.mu.Lock()
	defer s.mu.Unlock()

	var out []Entry
	for _, e := range s.rows {
		if e.CreatedAt.Format("2006-01-02") == day {
			out = append(out, e)
		}
	}
	return out
}

func (s *MemStore) Create(e NewEntry) Entry {
	s.mu.Lock()
	defer s.mu.Unlock()

	entry := Entry{
		ID:        newID(),
		Task:      e.Task,
		Minutes:   e.Minutes,
		CreatedAt: time.Now().UTC(),
	}
	s.rows[entry.ID] = entry
	return entry
}
