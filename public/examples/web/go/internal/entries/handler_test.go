// handler_test.go — the contract is tested over httptest: no server,
// no network. Table-driven, the Go way.
package entries

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func newTestHandler() *Handler {
	return NewHandler(NewMemStore(), NewDedup())
}

func post(t *testing.T, h *Handler, body, requestID string) *httptest.ResponseRecorder {
	t.Helper()
	req := httptest.NewRequest(http.MethodPost, "/api/time-entries", bytes.NewBufferString(body))
	req.Header.Set("Content-Type", "application/json")
	if requestID != "" {
		req.Header.Set("X-Request-Id", requestID)
	}
	rec := httptest.NewRecorder()
	h.Create(rec, req)
	return rec
}

func TestCreateValidatesSchema(t *testing.T) {
	h := newTestHandler()
	rec := post(t, h, `{"task":"","minutes":0}`, "req-1")

	if rec.Code != http.StatusBadRequest {
		t.Fatalf("expected 400, got %d", rec.Code)
	}
	var e apiError
	_ = json.NewDecoder(rec.Body).Decode(&e)
	if e.Code != "invalid_payload" {
		t.Fatalf("expected invalid_payload error, got %q", e.Code)
	}
}

func TestCreateIsIdempotent(t *testing.T) {
	h := newTestHandler()

	first := post(t, h, `{"task":"Deep work","minutes":90}`, "req-abc-123")
	second := post(t, h, `{"task":"Deep work","minutes":90}`, "req-abc-123")

	var e1, e2 Entry
	_ = json.NewDecoder(first.Body).Decode(&e1)
	_ = json.NewDecoder(second.Body).Decode(&e2)

	if e1.ID != e2.ID {
		t.Fatalf("retry created a duplicate: %s vs %s", e1.ID, e2.ID)
	}
}

func TestCreateRejectsNonJSON(t *testing.T) {
	h := newTestHandler()
	req := httptest.NewRequest(http.MethodPost, "/api/time-entries",
		bytes.NewBufferString(`{"task":"x","minutes":1}`))
	req.Header.Set("Content-Type", "text/plain")
	rec := httptest.NewRecorder()
	h.Create(rec, req)

	if rec.Code != http.StatusUnsupportedMediaType {
		t.Fatalf("expected 415, got %d", rec.Code)
	}
}

func TestCreateRejectsUnknownFields(t *testing.T) {
	h := newTestHandler()
	rec := post(t, h, `{"task":"x","minutes":1,"extra":true}`, "req-2")

	if rec.Code != http.StatusBadRequest {
		t.Fatalf("expected 400 for unknown field, got %d", rec.Code)
	}
}

func TestCreateRejectsOversizedBody(t *testing.T) {
	h := newTestHandler()
	big := `{"task":"` + strings.Repeat("x", 9*1024) + `","minutes":1}`
	rec := post(t, h, big, "req-3")

	if rec.Code != http.StatusRequestEntityTooLarge {
		t.Fatalf("expected 413, got %d", rec.Code)
	}
}

func TestCreateEnforcesBounds(t *testing.T) {
	h := newTestHandler()

	if rec := post(t, h, `{"task":"x","minutes":1441}`, "req-4"); rec.Code != http.StatusBadRequest {
		t.Fatalf("expected 400 for minutes>1440, got %d", rec.Code)
	}
	if rec := post(t, h, `{"task":"`+strings.Repeat("t", 256)+`","minutes":10}`, "req-5"); rec.Code != http.StatusBadRequest {
		t.Fatalf("expected 400 for task>255, got %d", rec.Code)
	}
}

func TestCreateRequiresRequestID(t *testing.T) {
	h := newTestHandler()
	rec := post(t, h, `{"task":"x","minutes":10}`, "")

	if rec.Code != http.StatusBadRequest {
		t.Fatalf("expected 400 without X-Request-Id, got %d", rec.Code)
	}
}

func TestListRejectsBadDay(t *testing.T) {
	h := newTestHandler()
	req := httptest.NewRequest(http.MethodGet, "/api/time-entries?day=not-a-date", nil)
	rec := httptest.NewRecorder()
	h.List(rec, req)

	if rec.Code != http.StatusBadRequest {
		t.Fatalf("expected 400 for bad day, got %d", rec.Code)
	}
}
