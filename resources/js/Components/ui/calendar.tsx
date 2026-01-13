import * as React from "react"
import { ChevronLeft, ChevronRight } from "lucide-react"
import { DayPicker } from "react-day-picker"

import { cn } from "@/lib/utils"

export type CalendarProps = React.ComponentProps<typeof DayPicker>

function Calendar({
  className,
  classNames,
  showOutsideDays = true,
  ...props
}: CalendarProps) {
  return (
    <DayPicker
      showOutsideDays={showOutsideDays}
      className={cn("p-3", className)}
      classNames={{
        months: "flex flex-col sm:flex-row gap-4",
        month: "flex flex-col gap-4",
        month_caption: "flex justify-center pt-1 relative items-center h-10",
        caption_label: "text-sm font-medium text-gray-900 dark:text-white",
        nav: "flex items-center gap-1",
        button_previous: cn(
          "absolute left-1 inline-flex items-center justify-center rounded-md text-sm font-medium",
          "h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100",
          "text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
        ),
        button_next: cn(
          "absolute right-1 inline-flex items-center justify-center rounded-md text-sm font-medium",
          "h-7 w-7 bg-transparent p-0 opacity-50 hover:opacity-100",
          "text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
        ),
        month_grid: "w-full border-collapse",
        weekdays: "flex",
        weekday: "text-gray-500 dark:text-gray-400 rounded-md w-9 font-normal text-[0.8rem] flex items-center justify-center h-9",
        week: "flex w-full mt-2",
        day: "h-9 w-9 text-center text-sm p-0 relative flex items-center justify-center",
        day_button: cn(
          "inline-flex items-center justify-center rounded-md text-sm font-normal",
          "h-9 w-9 p-0 hover:bg-gray-100 dark:hover:bg-gray-700",
          "text-gray-900 dark:text-white",
          "focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-800",
          "aria-selected:opacity-100"
        ),
        range_end: "day-range-end",
        selected: cn(
          "bg-primary text-white hover:bg-primary hover:text-white",
          "focus:bg-primary focus:text-white"
        ),
        today: "bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white",
        outside: "text-gray-400 dark:text-gray-500 opacity-50 aria-selected:bg-gray-100/50 dark:aria-selected:bg-gray-800/50",
        disabled: "text-gray-400 dark:text-gray-500 opacity-50",
        range_middle: "aria-selected:bg-gray-100 aria-selected:text-gray-900 dark:aria-selected:bg-gray-800 dark:aria-selected:text-white",
        hidden: "invisible",
        ...classNames,
      }}
      components={{
        Chevron: ({ orientation }) => {
          return orientation === 'left' ? (
            <ChevronLeft className="h-4 w-4" />
          ) : (
            <ChevronRight className="h-4 w-4" />
          )
        },
      }}
      {...props}
    />
  )
}
Calendar.displayName = "Calendar"

export { Calendar }
