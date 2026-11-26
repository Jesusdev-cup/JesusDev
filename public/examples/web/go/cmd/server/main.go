// main.go — wiring only: routes per domain on the standard ServeMux
// (Go 1.22 method+pattern routing), domain behind an interface.
// Middleware wraps the whole mux: headers → rate limit → routes.
package main

import (
	"log"
	"net/http"
	"time"

	"example.com/timeapi/internal/entries"
)

func main() {
	store := entries.NewMemStore()
	dedup := entries.NewDedup()
	h := entries.NewHandler(store, dedup)

	mux := http.NewServeMux()
	mux.HandleFunc("GET /api/time-entries", entries.RequirePerm("time.read", h.List))
	mux.HandleFunc("POST /api/time-entries", entries.RequirePerm("time.write", h.Create))
	mux.HandleFunc("GET /api/status", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		_, _ = w.Write([]byte(`{"ok":true}`))
	})

	// Stack: security headers → rate limit (60 req/min) → routes.
	handler := entries.SecurityHeaders(entries.RateLimit(60, time.Minute)(mux))

	srv := &http.Server{
		Addr:              ":8080",
		Handler:           handler,
		ReadHeaderTimeout: 5 * time.Second, // Slowloris guard — no unbounded reads.
		ReadTimeout:       10 * time.Second,
		WriteTimeout:      15 * time.Second,
		IdleTimeout:       60 * time.Second,
	}

	log.Println("listening on :8080")
	log.Fatal(srv.ListenAndServe())
}
