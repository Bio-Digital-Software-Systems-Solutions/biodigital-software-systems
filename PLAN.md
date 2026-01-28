# Plan: Department Statistics View Switcher

## Objective
Add a toggle to switch between two visualization modes in the Statistics tab of the Department Show page:
1. **Operational View** (current) - Bar charts, progress bars, tables (screenshot 1)
2. **Analytical View** (new) - Doughnut charts, line charts, horizontal bar charts (screenshot 2)

## Implementation Steps

### 1. Create `DepartmentStatisticsOperational.tsx` component
Extract the current statistics tab content (lines 1022-1509 of Show.tsx) into a dedicated component that receives `statistics`, `evolutionPeriod`, and `setEvolutionPeriod` as props.

### 2. Create `DepartmentStatisticsAnalytical.tsx` component
New visualization with:
- **3 Doughnut Charts** (using CSS conic-gradient, no external library):
  - "Charge Totale par Membre" (task volume by member)
  - "Succès par Membre" (completed tasks by member)
  - "Statut Global du Pipeline" (task status distribution with labels: Validé, En Analyse, Séquençage, Bloqué)
- **Line Chart** "Flux de Productivité Hebdomadaire" (weekly task evolution - created vs completed as lines)
- **Horizontal Bar Chart** "Comparatif d'Efficacité" (per-member completion rate as horizontal bars)

All charts built with pure CSS/Tailwind + SVG for the line chart. No external chart library needed.

### 3. Update `Show.tsx` Statistics Tab
- Add `statsViewMode` state: `'operational' | 'analytical'`
- Add a toggle (two buttons with icons) at the top of the Statistics tab to switch between views
- Render `DepartmentStatisticsOperational` or `DepartmentStatisticsAnalytical` based on the mode
- Pass the same `statistics` data (no backend changes needed)

### 4. Write Feature Tests
Add tests to `DepartmentControllerTest.php`:
- Test that `statistics` data is passed when user has permission
- Test that `statistics` data includes `tasks_by_member` and `performance` keys
- Test statistics not passed when user lacks permission

### 5. Write Vitest Frontend Tests
Create `resources/js/Pages/Departments/Show.test.tsx`:
- Test operational view renders by default
- Test switching to analytical view
- Test both views render correctly with data
- Test empty state handling

## No Backend Changes
Both views use the same `DepartmentStatistics` data already provided by the controller. The switch is purely frontend.

## Files to Create/Modify
- **Create:** `resources/js/Components/Department/DepartmentStatisticsOperational.tsx`
- **Create:** `resources/js/Components/Department/DepartmentStatisticsAnalytical.tsx`
- **Modify:** `resources/js/Pages/Departments/Show.tsx` (extract stats, add toggle)
- **Modify:** `tests/Feature/DepartmentControllerTest.php` (add statistics tests)
- **Create:** `resources/js/Pages/Departments/__tests__/StatisticsView.test.tsx`
