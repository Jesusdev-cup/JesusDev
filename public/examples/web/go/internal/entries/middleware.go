// middleware.go — the permission check as an http middleware.
// Same role as permission_callback: enforced before the handler runs.
package entries

import "net/http"

// RequirePerm wraps a handler with a permission check.
// The real implementation reads the user's permission set from auth.
func RequirePerm(perm string, next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		// In a real app the auth middleware populates this from the token.
		if r.Context().Value("permissions") == nil {
			writeErr(w, http.StatusForbidden, "forbidden", "missing permission: "+perm)
			return
		}
		next(w, r)
	}
}
