import * as React from "react"
import { createPortal } from "react-dom"

interface PopoverProps {
  children: React.ReactNode
  open?: boolean
  onOpenChange?: (open: boolean) => void
}

interface PopoverTriggerProps {
  asChild?: boolean
  children: React.ReactNode
}

interface PopoverContentProps {
  children: React.ReactNode
  className?: string
  align?: 'start' | 'center' | 'end'
  side?: 'top' | 'bottom'
  portal?: boolean
}

const PopoverContext = React.createContext<{
  isOpen: boolean
  setIsOpen: (open: boolean) => void
  triggerRef: React.RefObject<HTMLDivElement | null>
} | null>(null)

const Popover = ({ children, open, onOpenChange }: PopoverProps) => {
  const [internalIsOpen, setInternalIsOpen] = React.useState(false)
  const triggerRef = React.useRef<HTMLDivElement | null>(null)

  const isOpen = open !== undefined ? open : internalIsOpen
  const setIsOpen = (newOpen: boolean) => {
    if (onOpenChange) {
      onOpenChange(newOpen)
    }
    if (open === undefined) {
      setInternalIsOpen(newOpen)
    }
  }

  return (
    <PopoverContext.Provider value={{ isOpen, setIsOpen, triggerRef }}>
      <div className="relative" ref={triggerRef}>
        {children}
      </div>
    </PopoverContext.Provider>
  )
}

const PopoverTrigger = ({ asChild = false, children }: PopoverTriggerProps) => {
  const context = React.useContext(PopoverContext)
  if (!context) throw new Error("PopoverTrigger must be used within Popover")

  if (asChild && React.isValidElement(children)) {
    return React.cloneElement(children, {
      onClick: () => context.setIsOpen(!context.isOpen)
    } as any)
  }

  return (
    <button
      type="button"
      onClick={() => context.setIsOpen(!context.isOpen)}
    >
      {children}
    </button>
  )
}

const PopoverContent = ({ children, className = "", align = "start", side = "bottom", portal = false }: PopoverContentProps) => {
  const context = React.useContext(PopoverContext)
  if (!context) throw new Error("PopoverContent must be used within Popover")
  if (!context.isOpen) return null

  if (portal) {
    return <PopoverContentPortal context={context} className={className} align={align} side={side}>
      {children}
    </PopoverContentPortal>
  }

  const alignClass = {
    start: 'left-0',
    center: 'left-1/2 -translate-x-1/2',
    end: 'right-0'
  }[align]

  const sideClass = side === 'top' ? 'bottom-full mb-2' : 'top-full mt-2'

  return (
    <>
      <div
        className="fixed inset-0 z-40"
        onClick={() => context.setIsOpen(false)}
      />
      <div className={`absolute z-50 ${sideClass} bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg ${alignClass} ${className}`}>
        {children}
      </div>
    </>
  )
}

function PopoverContentPortal({
  children,
  context,
  className,
  align,
  side,
}: {
  children: React.ReactNode
  context: { isOpen: boolean; setIsOpen: (open: boolean) => void; triggerRef: React.RefObject<HTMLDivElement | null> }
  className: string
  align: 'start' | 'center' | 'end'
  side: 'top' | 'bottom'
}) {
  const [style, setStyle] = React.useState<React.CSSProperties>({})
  const contentRef = React.useRef<HTMLDivElement>(null)

  React.useLayoutEffect(() => {
    const trigger = context.triggerRef.current
    if (!trigger) return

    const rect = trigger.getBoundingClientRect()
    const contentEl = contentRef.current
    const contentHeight = contentEl?.offsetHeight ?? 0

    let top: number
    if (side === 'top') {
      top = rect.top - contentHeight - 8
      if (top < 0) {
        top = rect.bottom + 8
      }
    } else {
      top = rect.bottom + 8
      if (top + contentHeight > window.innerHeight) {
        top = rect.top - contentHeight - 8
      }
    }

    let left: number
    if (align === 'start') {
      left = rect.left
    } else if (align === 'end') {
      left = rect.right
    } else {
      left = rect.left + rect.width / 2
    }

    setStyle({ position: 'fixed', top, left, zIndex: 9999 })
  }, [context.triggerRef, side, align])

  const transformClass = align === 'center' ? '-translate-x-1/2' : ''

  return createPortal(
    <>
      <div
        className="fixed inset-0 z-[9998]"
        onClick={() => context.setIsOpen(false)}
      />
      <div
        ref={contentRef}
        style={style}
        className={`${transformClass} bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg ${className}`}
      >
        {children}
      </div>
    </>,
    document.body
  )
}

export { Popover, PopoverTrigger, PopoverContent }
