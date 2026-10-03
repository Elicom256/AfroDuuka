import * as React from "react"

const MOBILE_BREAKPOINT = 768

const mobileQuery = `(max-width: ${MOBILE_BREAKPOINT - 1}px)`

// Seeded during the first render rather than from an effect: the app is a client-only
// Vite SPA, so `window` is always defined here, and reading it in an effect made every
// mount cost an extra render before the value settled.
const readIsMobile = () =>
  typeof window !== 'undefined' && window.matchMedia(mobileQuery).matches

export function useIsMobile() {
  const [isMobile, setIsMobile] = React.useState<boolean>(readIsMobile)

  React.useEffect(() => {
    const mql = window.matchMedia(mobileQuery)
    const onChange = () => {
      setIsMobile(window.innerWidth < MOBILE_BREAKPOINT)
    }
    mql.addEventListener("change", onChange)
    return () => mql.removeEventListener("change", onChange)
  }, [])

  return isMobile
}
