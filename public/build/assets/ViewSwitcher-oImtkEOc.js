import{j as r}from"./app-DXp2eLF3.js";import{F as t}from"./Squares2X2Icon-odnu-vVv.js";import{F as o}from"./ListBulletIcon-BfiEBphp.js";import{b as i}from"./PencilSquareIcon-CcafV656.js";function x({currentView:e,onViewChange:s,showCalendar:a=!1}){return r.jsxs("div",{className:"inline-flex rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-0.5 sm:p-1 flex-shrink-0",children:[r.jsx("button",{onClick:()=>s("grid"),className:`
                    px-2 py-1.5 sm:px-3 sm:py-2 rounded-md transition-colors
                    ${e==="grid"?"bg-icc-blue text-white":"text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"}
                `,title:"Vue grille",children:r.jsx(t,{className:"h-4 w-4 sm:h-5 sm:w-5"})}),r.jsx("button",{onClick:()=>s("list"),className:`
                    px-2 py-1.5 sm:px-3 sm:py-2 rounded-md transition-colors
                    ${e==="list"?"bg-icc-blue text-white":"text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"}
                `,title:"Vue liste",children:r.jsx(o,{className:"h-4 w-4 sm:h-5 sm:w-5"})}),a&&r.jsx("button",{onClick:()=>s("calendar"),className:`
                        px-2 py-1.5 sm:px-3 sm:py-2 rounded-md transition-colors
                        ${e==="calendar"?"bg-icc-blue text-white":"text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"}
                    `,title:"Vue calendrier",children:r.jsx(i,{className:"h-4 w-4 sm:h-5 sm:w-5"})})]})}export{x as V};
