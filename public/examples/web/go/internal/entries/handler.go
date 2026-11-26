// handler.go — the REST contract over the domain: validate → dedup → domain.
// The error shape is part of the contract; internals never leak.
// Maximum validation: strict JSON, bounded body, typed bounds, no leaks.
package entries

import (
	"encoding/json"
	"errors"
	"net/http"
	"regexp"
	"strings"
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

var dayRe = regexp.MustCompile(`^\d{4}-\d{2}-\d{2}$`)

// List handles GET /api/time-entries?day=YYYY-MM-DD.
func (h *Handler) List(w http.ResponseWriter, r *http.Request) {
	day := r.URL.Query().Get("day")
	if day == "" {
		day = time.Now().UTC().Format("2006-01-02")
	}
	if !dayRe.MatchString(day) {
		writeErr(w, http.StatusBadRequest, "invalid_day", "day must be YYYY-MM-DD")
		return
	}
	writeJSON(w, http.StatusOK, h.store.List(day))
}

// maxBody is the contract's declared body ceiling — oversized payloads are
// rejected before a single byte is parsed.
const maxBody = 8 << 10 // 8 KiB

// Create handles POST /api/time-entries — schema → dedup → domain.
func (h *Handler) Create(w http.ResponseWriter, r *http.Request) {
	// The contract declares its media type; anything else is refused.
	if ct := r.Header.Get("Content-Type"); !strings.HasPrefix(ct, "application/json") {
		writeErr(w, http.StatusUnsupportedMediaType, "unsupported_media_type", "Content-Type must be application/json")
		return
	}

	// Bounded body + strict decoder: unknown fields and oversized payloads
	// are rejected before they touch the domain.
	r.Body = http.MaxBytesReader(w, r.Body, maxBody)
	dec := json.NewDecoder(r.Body)
	dec.DisallowUnknownFields()

	var in NewEntry
	if err := dec.Decode(&in); err != nil {
		var maxErr *http.MaxBytesError
		if errors.As(err, &maxErr) {
			writeErr(w, http.StatusRequestEntityTooLarge, "payload_too_large", "body exceeds 8 KiB")
			return
		}
		writeErr(w, http.StatusBadRequest, "invalid_payload", "malformed JSON or unknown field")
		return
	}

	// Domain bounds: the schema's full range, enforced here too.
	in.Task = strings.TrimSpace(in.Task)
	if in.Task == "" || len(in.Task) > 255 || in.Minutes < 1 || in.Minutes > 1440 {
		writeErr(w, http.StatusBadRequest, "invalid_payload", "task (1-255 chars) and minutes (1-1440) are required")
		return
	}

	// Dedup happens before the mutation runs.
	requestID := r.Header.Get("X-Request-Id")
	if requestID == "" {
		writeErr(w, http.StatusBadRequest, "missing_request_id", "X-Request-Id header is required")
		return
	}
	if dup := h.dedup.Seen(requestID); dup != nil {
		writeJSON(w, http.StatusOK, dup)
		return
	}

	entry := h.store.Create(in)
	h.dedup.Remember(requestID, entry)
	writeJSON(w, http.StatusCreated, entry)
}
