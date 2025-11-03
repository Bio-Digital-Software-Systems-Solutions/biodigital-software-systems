import{j as r}from"./app-DG1TC26c.js";import{F as o}from"./Squares2X2Icon-De5GHA0P.js";import{F as s}from"./ListBulletIcon-DhLoCZSt.js";import{F as i}from"./UserGroupIcon-B-7a5fzj.js";function n({currentView:e,onViewChange:a,showCalendar:t=!1}){return r.jsxs("div",{className:"inline-flex rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-1",children:[r.jsx("button",{onClick:()=>a("grid"),className:`
                    px-3 py-2 rounded-md transition-colors
                    ${e==="grid"?"bg-icc-blue text-white":"text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"}
                `,title:"Vue grille",children:r.jsx(o,{className:"h-5 w-5"})}),r.jsx("button",{onClick:()=>a("list"),className:`
                    px-3 py-2 rounded-md transition-colors
                    ${e==="list"?"bg-icc-blue text-white":"text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"}
                `,title:"Vue liste",children:r.jsx(s,{className:"h-5 w-5"})}),t&&r.jsx("button",{onClick:()=>a("calendar"),className:`
                        px-3 py-2 rounded-md transition-colors
                        ${e==="calendar"?"bg-icc-blue text-white":"text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"}
                    `,title:"Vue calendrier",children:r.jsx(i,{className:"h-5 w-5"})})]})}export{n as V};
