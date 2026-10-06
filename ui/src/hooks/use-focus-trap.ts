import * as React from 'react'

const FOCUSABLE =
  "a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex='-1'])"

type Options = {
  active?: boolean
  onEscape?: () => void
}

/**
 * Keeps keyboard focus inside a hand-rolled dialog and restores it to the
 * trigger on close. The shadcn <Dialog> gets this from Radix; the overlays that
 * predate it do not, so Tab could walk the page behind the modal and Escape did
 * nothing. `active` must track whether the dialog is actually mounted.
 */
export function useFocusTrap<T extends HTMLElement>(
  ref: React.RefObject<T | null>,
  { active = true, onEscape }: Options = {},
) {
  const onEscapeRef = React.useRef(onEscape)
  React.useEffect(() => {
    onEscapeRef.current = onEscape
  }, [onEscape])

  React.useEffect(() => {
    if (!active) return
    const node = ref.current
    if (!node) return

    const previouslyFocused = document.activeElement as HTMLElement | null
    const focusables = () =>
      Array.from(node.querySelectorAll<HTMLElement>(FOCUSABLE)).filter(
        (el) => el.offsetParent !== null,
      )

    focusables()[0]?.focus()

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onEscapeRef.current?.()
        return
      }
      if (event.key !== 'Tab') return
      const items = focusables()
      if (!items.length) return
      const first = items[0]
      const last = items[items.length - 1]
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last.focus()
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
      }
    }

    node.addEventListener('keydown', onKeyDown)
    return () => {
      node.removeEventListener('keydown', onKeyDown)
      previouslyFocused?.focus?.()
    }
  }, [active, ref])
}
