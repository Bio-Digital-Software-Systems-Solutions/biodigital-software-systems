import * as React from "react"
import { XMarkIcon } from '@heroicons/react/24/outline'

interface DialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  children: React.ReactNode
}

interface DialogContentProps {
  children: React.ReactNode
  className?: string
  onClose?: () => void
}

const Dialog = ({ open, onOpenChange, children }: DialogProps) => {
  if (!open) return null

  return (
    <div className="fixed inset-0 z-50">
      <div
        className="fixed inset-0 bg-black/50"
        onClick={() => onOpenChange(false)}
      />
      <div
        className="fixed inset-0 flex items-center justify-center p-4"
        onClick={() => onOpenChange(false)}
      >
        {React.Children.map(children, child => {
          if (React.isValidElement(child)) {
            return React.cloneElement(child as React.ReactElement<any>, {
              onClose: () => onOpenChange(false)
            })
          }
          return child
        })}
      </div>
    </div>
  )
}

const DialogContent = ({ children, className = "", onClose }: DialogContentProps) => {
  const hasOverflowVisible = className.includes('overflow-visible')
  return (
    <div
      className={`relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] ${hasOverflowVisible ? '' : 'overflow-y-auto'} ${className}`}
      onClick={(e) => e.stopPropagation()}
    >
      {onClose && (
        <button
          onClick={onClose}
          className="absolute top-4 right-4 z-10 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          aria-label="Fermer"
        >
          <XMarkIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
        </button>
      )}
      {children}
    </div>
  )
}

const DialogHeader = ({ children, className = "" }: { children: React.ReactNode; className?: string }) => {
  return (
    <div className={`px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700 ${className}`}>
      {children}
    </div>
  )
}

const DialogTitle = ({ children, className = "" }: { children: React.ReactNode; className?: string }) => {
  return (
    <h2 className={`text-xl font-semibold text-gray-900 dark:text-white ${className}`}>
      {children}
    </h2>
  )
}

const DialogDescription = ({ children, className = "" }: { children: React.ReactNode; className?: string }) => {
  return (
    <div className={`mt-2 text-sm text-gray-500 dark:text-gray-400 ${className}`}>
      {children}
    </div>
  )
}

const DialogFooter = ({ children, className = "" }: { children: React.ReactNode; className?: string }) => {
  return (
    <div className={`px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3 rounded-b-lg ${className}`}>
      {children}
    </div>
  )
}

export { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter }

