// middleware.go — the permission check as an http middleware.
// Same role as permission_callback: enforced before the handler runs.
package entries

import (
	"net/http"
	"strconv"
	"sync"
	"time"
)

// RequirePerm wraps a handler with a permission check.
// 401 when there's no principal, 403 when it lacks the permission.
func RequirePerm(perm string, next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		perms, ok := r.Context().Value("permissions").([]string)
		if !ok {
			writeErr(w, http.StatusUnauthorized, "unauthenticated", "authentication required")
			return
		}
		for _, p := range perms {
			if p == perm {
				next(w, r)
				return
			}
		}
		writeErr(w, http.StatusForbidden, "forbidden", "missing permission: "+perm)
	}
}

// SecurityHeaders adds the baseline response headers — no stack leaks.
func SecurityHeaders(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("X-Content-Type-Options", "nosniff")
		w.Header().Set("X-Frame-Options", "DENY")
		w.Header().Set("Referrer-Policy", "strict-origin-when-cross-origin")
		w.Header().Set("Content-Security-Policy", "default-src 'none'")
		next.ServeHTTP(w, r)
	})
}

// RateLimit is a fixed-window limiter keyed by client IP + route.
// Mutations get a tighter budget than reads; abuse gets the contract's 429.
func RateLimit(limit int, window time.Duration) func(http.Handler) http.Handler {
	var mu sync.Mutex
	type bucket struct {
		count   int
		resetAt time.Time
	}
	buckets := map[string]*bucket{}

	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			key := r.RemoteAddr + " " + r.Method + " " + r.URL.Path
			now := time.Now()

			mu.Lock()
			b, ok := buckets[key]
			if !ok || now.After(b.resetAt) {
				b = &bucket{resetAt: now.Add(window)}
				buckets[key] = b
			}
			b.count++
			over, retry := b.count > limit, int(time.Until(b.resetAt).Seconds())
			mu.Unlock()

			w.Header().Set("X-RateLimit-Limit", strconv.Itoa(limit))
			if over {
				w.Header().Set("Retry-After", strconv.Itoa(retry))
				writeErr(w, http.StatusTooManyRequests, "rate_limited", "too many requests")
				return
			}
			next.ServeHTTP(w, r)
		})
	}
}
