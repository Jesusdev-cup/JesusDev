// main.go — wiring only: routes per domain on the standard ServeMux
// (Go 1.22 method+pattern routing), domain behind an interface.
package main

import (
	"log"
	"net/http"

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

	log.Println("listening on :8080")
	log.Fatal(http.ListenAndServe(":8080", mux))
}
