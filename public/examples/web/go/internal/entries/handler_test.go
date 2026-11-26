// handler_test.go — the contract is tested over httptest: no server,
// no network. Table-driven, the Go way.
package entries

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
)

func newTestHandler() *Handler {
	return NewHandler(NewMemStore(), NewDedup())
}

func TestCreateValidatesSchema(t *testing.T) {
	h := newTestHandler()
	req := httptest.NewRequest(http.MethodPost, "/api/time-entries",
		bytes.NewBufferString(`{"task":"","minutes":0}`))
	rec := httptest.NewRecorder()

	h.Create(rec, req)

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
	body := bytes.NewBufferString(`{"task":"Deep work","minutes":90}`)

	first := httptest.NewRecorder()
	req1 := httptest.NewRequest(http.MethodPost, "/api/time-entries", body)
	req1.Header.Set("X-Request-Id", "req-abc-123")
	h.Create(first, req1)

	second := httptest.NewRecorder()
	req2 := httptest.NewRequest(http.MethodPost, "/api/time-entries",
		bytes.NewBufferString(`{"task":"Deep work","minutes":90}`))
	req2.Header.Set("X-Request-Id", "req-abc-123")
	h.Create(second, req2)

	var e1, e2 Entry
	_ = json.NewDecoder(first.Body).Decode(&e1)
	_ = json.NewDecoder(second.Body).Decode(&e2)

	if e1.ID != e2.ID {
		t.Fatalf("retry created a duplicate: %s vs %s", e1.ID, e2.ID)
	}
}
