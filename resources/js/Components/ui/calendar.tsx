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
        months: "relative flex flex-col sm:flex-row gap-4",
        month: "flex flex-col gap-4",
        month_caption: "flex justify-center items-center h-10",
        caption_label: "text-base font-bold text-gray-900 dark:text-white",
        nav: "absolute inset-x-0 top-0 h-10 flex items-center justify-between px-1 pointer-events-none",
        button_previous: cn(
          "pointer-events-auto inline-flex items-center justify-center rounded-md",
          "h-8 w-8 bg-transparent p-0 opacity-50 hover:opacity-100",
          "text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-opacity"
        ),
        button_next: cn(
          "pointer-events-auto inline-flex items-center justify-center rounded-md",
          "h-8 w-8 bg-transparent p-0 opacity-50 hover:opacity-100",
          "text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-opacity"
        ),
        month_grid: "w-full border-collapse",
        weekdays: "flex",
        weekday: "text-gray-500 dark:text-gray-400 rounded-md w-9 font-normal text-[0.8rem] flex items-center justify-center h-9",
        week: "flex w-full mt-2",
        day: "h-9 w-9 text-center text-sm p-0 relative flex items-center justify-center",
        day_button: cn(
          "inline-flex items-center justify-center rounded-full text-sm font-normal",
          "h-9 w-9 p-0 hover:bg-gray-100 dark:hover:bg-gray-700",
          "text-gray-900 dark:text-white transition-colors",
          "focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-800",
          "aria-selected:opacity-100"
        ),
        range_end: "day-range-end",
        selected: cn(
          "bg-primary text-white hover:bg-primary hover:text-white rounded-full",
          "focus:bg-primary focus:text-white"
        ),
        today: "bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary font-semibold",
        outside: "text-gray-400 dark:text-gray-500 opacity-50 aria-selected:bg-gray-100/50 dark:aria-selected:bg-gray-800/50",
        disabled: "text-gray-400 dark:text-gray-500 opacity-50",
        range_middle: "aria-selected:bg-primary/10 aria-selected:text-primary dark:aria-selected:bg-primary/20",
        hidden: "invisible",
        ...classNames,
      }}
      components={{
        Chevron: ({ orientation }) =>
          orientation === "left"
            ? <ChevronLeft className="h-5 w-5" />
            : <ChevronRight className="h-5 w-5" />,
      }}
      {...props}
    />
  )
}
Calendar.displayName = "Calendar"

export { Calendar }
