import * as React from "react"
import { ChevronDownIcon } from '@heroicons/react/24/outline'

interface AccordionProps {
  children: React.ReactNode
}

interface AccordionItemProps {
  value: string
  children: React.ReactNode
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

const Accordion = ({ children }: AccordionProps) => {
  const [openItem, setOpenItem] = React.useState<string | null>(null)

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

const AccordionItem = ({ value, children }: AccordionItemProps) => {
  const context = React.useContext(AccordionContext)
  if (!context) throw new Error("AccordionItem must be used within Accordion")

  const isOpen = context.openItem === value
  const toggle = () => {
    context.setOpenItem(isOpen ? null : value)
  }

  return (
    <AccordionItemContext.Provider value={{ value, isOpen, toggle }}>
      <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
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
      <ChevronDownIcon
        className={`h-5 w-5 text-gray-500 transition-transform ${context.isOpen ? 'rotate-180' : ''}`}
      />
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
