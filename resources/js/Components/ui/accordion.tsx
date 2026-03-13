import * as React from "react"
import { PlusIcon, MinusIcon } from '@heroicons/react/24/outline'

interface AccordionProps {
  children: React.ReactNode
  defaultValue?: string | null
}

interface AccordionItemProps {
  value: string
  children: React.ReactNode
  className?: string
}

interface AccordionTriggerProps {
  children: React.ReactNode
  className?: string
}

interface AccordionContentProps {
  children: React.ReactNode
  className?: string
}

const AccordionContext = React.createContext<{
  openItem: string | null
  setOpenItem: (value: string | null) => void
} | null>(null)

const Accordion = ({ children, defaultValue = null }: AccordionProps) => {
  const [openItem, setOpenItem] = React.useState<string | null>(defaultValue)

  return (
    <AccordionContext.Provider value={{ openItem, setOpenItem }}>
      <div className="space-y-2">
        {children}
      </div>
    </AccordionContext.Provider>
  )
}

const AccordionItemContext = React.createContext<{
  value: string
  isOpen: boolean
  toggle: () => void
} | null>(null)

const AccordionItem = ({ value, children, className }: AccordionItemProps) => {
  const context = React.useContext(AccordionContext)
  if (!context) throw new Error("AccordionItem must be used within Accordion")

  const isOpen = context.openItem === value
  const toggle = () => {
    context.setOpenItem(isOpen ? null : value)
  }

  return (
    <AccordionItemContext.Provider value={{ value, isOpen, toggle }}>
      <div className={className ?? "border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"}>
        {children}
      </div>
    </AccordionItemContext.Provider>
  )
}

const AccordionTrigger = ({ children, className = "" }: AccordionTriggerProps) => {
  const context = React.useContext(AccordionItemContext)
  if (!context) throw new Error("AccordionTrigger must be used within AccordionItem")

  return (
    <button
      type="button"
      onClick={context.toggle}
      className={`flex items-center justify-between w-full px-4 py-3 text-left font-medium text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors ${className}`}
    >
      {children}
      {context.isOpen ? (
        <MinusIcon className="h-5 w-5 text-gray-500 flex-shrink-0" />
      ) : (
        <PlusIcon className="h-5 w-5 text-gray-500 flex-shrink-0" />
      )}
    </button>
  )
}

const AccordionContent = ({ children, className = "" }: AccordionContentProps) => {
  const context = React.useContext(AccordionItemContext)
  if (!context) throw new Error("AccordionContent must be used within AccordionItem")

  if (!context.isOpen) return null

  return (
    <div className={`px-4 py-3 border-t border-gray-200 dark:border-gray-700 ${className}`}>
      {children}
    </div>
  )
}

export { Accordion, AccordionItem, AccordionTrigger, AccordionContent }
