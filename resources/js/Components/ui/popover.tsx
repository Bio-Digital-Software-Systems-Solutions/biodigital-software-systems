import * as React from "react"

interface PopoverProps {
  children: React.ReactNode
}

interface PopoverTriggerProps {
  asChild?: boolean
  children: React.ReactNode
}

interface PopoverContentProps {
  children: React.ReactNode
  className?: string
}

const PopoverContext = React.createContext<{
  isOpen: boolean
  setIsOpen: (open: boolean) => void
} | null>(null)

const Popover = ({ children }: PopoverProps) => {
  const [isOpen, setIsOpen] = React.useState(false)

  return (
    <PopoverContext.Provider value={{ isOpen, setIsOpen }}>
      <div className="relative">
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

const PopoverContent = ({ children, className = "" }: PopoverContentProps) => {
  const context = React.useContext(PopoverContext)
  if (!context) throw new Error("PopoverContent must be used within Popover")
  if (!context.isOpen) return null

  return (
    <>
      <div
        className="fixed inset-0 z-40"
        onClick={() => context.setIsOpen(false)}
      />
      <div className={`absolute z-50 mt-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg ${className}`}>
        {children}
      </div>
    </>
  )
}

export { Popover, PopoverTrigger, PopoverContent }