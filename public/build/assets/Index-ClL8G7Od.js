import{R as u,j as e,H as g,L as s,a as n}from"./app-N5rzxZ-3.js";import{D as h}from"./DashboardLayout-G9hBhWeZ.js";import{t}from"./index-xqkJVkEw.js";import{D as f}from"./delete-confirmation-dialog-0UNqJ1zS.js";import{F as l}from"./PlusIcon-8qEqU-YM.js";import{F as b}from"./PlayIcon-B9qcYS7C.js";import{F as y}from"./EyeIcon-B8cE72x2.js";import{F as v}from"./PencilIcon-Cu168J7T.js";import{F as j}from"./DocumentDuplicateIcon-CwNVuOAS.js";import{F as k}from"./TrashIcon-CLTLSzDe.js";import"./transition-nJ9AQuj0.js";import"./ChevronDownIcon-wF7HzYdP.js";import"./UserGroupIcon-CxqPgnNX.js";import"./index-lWbcgXgr.js";import"./logger-BM3S30lt.js";import"./dialog-gCf2L_vQ.js";import"./button-Chdqwj8o.js";import"./utils-DOYE-kNG.js";import"./badge-Dtt4crS3.js";import"./shield-alert-BtO4_ZPw.js";import"./createLucideIcon-B8kn40CP.js";import"./triangle-alert-Dib8Z6V9.js";import"./arrow-left-CsH3tQ60.js";import"./index-BzW5G-pZ.js";import"./index-CAFI5w6t.js";import"./HomeIcon-iioL0FnU.js";import"./HeartIcon-Cu_pRxr9.js";import"./ClockIcon-CbL7bsgM.js";import"./ChatBubbleLeftRightIcon-QmVxsJEF.js";import"./DocumentTextIcon-BaWsIjet.js";import"./EnvelopeIcon-CRrWBTPc.js";import"./ClipboardDocumentCheckIcon-CUOxQPda.js";import"./Bars3Icon-DJiFFF2Q.js";const w={draft:{label:"Brouillon",color:"bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"},active:{label:"Actif",color:"bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"},deprecated:{label:"Obsolète",color:"bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"}};function se({workflows:m}){const i=m?.data||[],[a,o]=u.useState(null),c=()=>{a&&n.delete(route("workflows.destroy",a.uuid),{onSuccess:()=>{t.success("Workflow supprimé avec succès"),o(null)},onError:()=>{t.error("Erreur lors de la suppression")}})},p=r=>{n.post(route("workflows.duplicate",r.uuid),{},{onSuccess:()=>{t.success("Workflow dupliqué avec succès")},onError:()=>{t.error("Erreur lors de la duplication")}})},x=r=>{n.post(route("workflows.activate",r.uuid),{},{onSuccess:()=>{t.success("Workflow activé avec succès")},onError:()=>{t.error("Erreur lors de l'activation")}})};return e.jsxs(h,{children:[e.jsx(g,{title:"Workflows"}),e.jsx("div",{className:"py-6",children:e.jsxs("div",{className:"max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",children:[e.jsxs("div",{className:"flex items-center justify-between mb-6",children:[e.jsxs("div",{children:[e.jsx("h1",{className:"text-2xl font-bold text-gray-900 dark:text-white",children:"Workflows"}),e.jsx("p",{className:"text-sm text-gray-500 dark:text-gray-400 mt-1",children:"Gérez vos workflows et automatisations"})]}),e.jsxs(s,{href:route("workflows.create"),className:`
                                inline-flex items-center gap-2 px-4 py-2 rounded-md
                                bg-primary text-white font-medium
                                hover:bg-primary/90 transition-colors
                            `,children:[e.jsx(l,{className:"h-5 w-5"}),"Nouveau workflow"]})]}),i.length===0?e.jsxs("div",{className:`
                            bg-white dark:bg-gray-800 rounded-lg
                            border border-gray-200 dark:border-gray-700
                            p-12 text-center
                        `,children:[e.jsx("p",{className:"text-gray-500 dark:text-gray-400 mb-4",children:"Aucun workflow créé"}),e.jsxs(s,{href:route("workflows.create"),className:`
                                    inline-flex items-center gap-2 px-4 py-2 rounded-md
                                    bg-primary text-white font-medium
                                    hover:bg-primary/90 transition-colors
                                `,children:[e.jsx(l,{className:"h-5 w-5"}),"Créer votre premier workflow"]})]}):e.jsx("div",{className:"grid gap-4",children:i.map(r=>{const d=w[r.status];return e.jsxs("div",{className:`
                                            bg-white dark:bg-gray-800 rounded-lg
                                            border border-gray-200 dark:border-gray-700
                                            p-4 flex items-center justify-between
                                            hover:shadow-md transition-shadow
                                        `,children:[e.jsxs("div",{className:"flex-1 min-w-0",children:[e.jsxs("div",{className:"flex items-center gap-3 mb-1",children:[e.jsx(s,{href:route("workflows.show",r.uuid),className:"text-lg font-medium text-gray-900 dark:text-white truncate hover:text-primary dark:hover:text-primary transition-colors",children:r.name}),e.jsx("span",{className:`px-2 py-0.5 rounded text-xs font-medium ${d.color}`,children:d.label})]}),r.description&&e.jsx("p",{className:"text-sm text-gray-500 dark:text-gray-400 truncate",children:r.description}),e.jsxs("div",{className:"flex items-center gap-4 mt-2 text-xs text-gray-400",children:[e.jsxs("span",{children:[r.steps_count||0," étapes"]}),e.jsxs("span",{children:["Version ",r.version]}),r.department&&e.jsx("span",{children:r.department.name})]})]}),e.jsxs("div",{className:"flex items-center gap-2 ml-4",children:[r.status==="draft"&&e.jsx("button",{type:"button",onClick:()=>x(r),className:`
                                                        p-2 rounded-md
                                                        text-green-600 hover:bg-green-50
                                                        dark:text-green-400 dark:hover:bg-green-900/20
                                                    `,title:"Activer",children:e.jsx(b,{className:"h-5 w-5"})}),e.jsx(s,{href:route("workflows.show",r.uuid),className:`
                                                    p-2 rounded-md
                                                    text-gray-600 hover:bg-gray-100
                                                    dark:text-gray-400 dark:hover:bg-gray-700
                                                `,title:"Voir",children:e.jsx(y,{className:"h-5 w-5"})}),e.jsx(s,{href:route("workflows.edit",r.uuid),className:`
                                                    p-2 rounded-md
                                                    text-gray-600 hover:bg-gray-100
                                                    dark:text-gray-400 dark:hover:bg-gray-700
                                                `,title:"Modifier",children:e.jsx(v,{className:"h-5 w-5"})}),e.jsx("button",{type:"button",onClick:()=>p(r),className:`
                                                    p-2 rounded-md
                                                    text-gray-600 hover:bg-gray-100
                                                    dark:text-gray-400 dark:hover:bg-gray-700
                                                `,title:"Dupliquer",children:e.jsx(j,{className:"h-5 w-5"})}),e.jsx("button",{type:"button",onClick:()=>o(r),className:`
                                                    p-2 rounded-md
                                                    text-red-600 hover:bg-red-50
                                                    dark:text-red-400 dark:hover:bg-red-900/20
                                                `,title:"Supprimer",children:e.jsx(k,{className:"h-5 w-5"})})]})]},r.uuid)})})]})}),e.jsx(f,{open:!!a,onOpenChange:r=>!r&&o(null),onConfirm:c,title:"Supprimer le workflow",description:`Êtes-vous sûr de vouloir supprimer le workflow "${a?.name}" ? Cette action est irréversible.`})]})}export{se as default};
