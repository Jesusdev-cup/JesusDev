// dedup.go — request-id dedup: a retry with the same X-Request-Id
// replays the stored entry instead of creating a duplicate.
package entries

import "sync"

// Dedup tracks request ids → stored responses.
type Dedup struct {
	mu   sync.Mutex
	seen map[string]Entry
}

func NewDedup() *Dedup {
	return &Dedup{seen: make(map[string]Entry)}
}

// Seen returns the stored entry if this request id already ran.
func (d *Dedup) Seen(requestID string) *Entry {
	d.mu.Lock()
	defer d.mu.Unlock()
	if e, ok := d.seen[requestID]; ok {
		return &e
	}
	return nil
}

// Remember stores the response for future retries.
func (d *Dedup) Remember(requestID string, e Entry) {
	if requestID == "" {
		return
	}
	d.mu.Lock()
	defer d.mu.Unlock()
	d.seen[requestID] = e
}
