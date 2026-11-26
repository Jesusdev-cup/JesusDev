// handler.go — the REST contract over the domain: validate → dedup → domain.
// The error shape is part of the contract; internals never leak.
package entries

import (
	"encoding/json"
	"net/http"
	"time"
)

// Handler wires the domain to the HTTP contract.
type Handler struct {
	store Store
	dedup *Dedup
}

func NewHandler(store Store, dedup *Dedup) *Handler {
	return &Handler{store: store, dedup: dedup}
}

// apiError is the contract's error shape.
type apiError struct {
	Code    string `json:"code"`
	Message string `json:"message"`
}

func writeJSON(w http.ResponseWriter, status int, v any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(v)
}

func writeErr(w http.ResponseWriter, status int, code, msg string) {
	writeJSON(w, status, apiError{Code: code, Message: msg})
}

// List handles GET /api/time-entries?day=YYYY-MM-DD.
func (h *Handler) List(w http.ResponseWriter, r *http.Request) {
	day := r.URL.Query().Get("day")
	if day == "" {
		day = time.Now().UTC().Format("2006-01-02")
	}
	writeJSON(w, http.StatusOK, h.store.List(day))
}

// Create handles POST /api/time-entries — schema → dedup → domain.
func (h *Handler) Create(w http.ResponseWriter, r *http.Request) {
	var in NewEntry
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil || in.Task == "" || in.Minutes < 1 {
		writeErr(w, http.StatusBadRequest, "invalid_payload", "task and minutes are required")
		return
	}

	// Dedup happens before the mutation runs.
	requestID := r.Header.Get("X-Request-Id")
	if dup := h.dedup.Seen(requestID); dup != nil {
		writeJSON(w, http.StatusOK, dup)
		return
	}

	entry := h.store.Create(in)
	h.dedup.Remember(requestID, entry)
	writeJSON(w, http.StatusCreated, entry)
}
