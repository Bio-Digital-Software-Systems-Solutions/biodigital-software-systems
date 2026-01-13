import{h as u,j as e,H as g,L as s,a as n}from"./app-BMnT2SOc.js";import{D as h}from"./DashboardLayout-Bmur6ODn.js";import{t}from"./index-C7s8rPHZ.js";import{D as f}from"./delete-confirmation-dialog-6Zbg6w3i.js";import{F as l}from"./PlusIcon-B0HmtM4U.js";import{F as b}from"./PlayIcon-BERlxpxg.js";import{F as y}from"./EyeIcon-DFgoix6X.js";import{F as v}from"./PencilIcon-BPNzIHlu.js";import{F as j}from"./DocumentDuplicateIcon-C8jkbIkW.js";import{F as k}from"./TrashIcon-DBmxbnyF.js";import"./transition-CBWOqIIm.js";import"./ChevronDownIcon-Dtbp_Tna.js";import"./UserGroupIcon-CXDHf5GX.js";import"./PencilSquareIcon-C_UlPCiG.js";import"./logger-BM3S30lt.js";import"./dialog-CtGhbBSL.js";import"./button-CDsmFIDE.js";import"./utils-DOYE-kNG.js";import"./badge-DbA8YUxG.js";import"./shield-alert-DDmXP3ae.js";import"./createLucideIcon-B9NU2kaB.js";import"./triangle-alert-DU9A3WQD.js";import"./arrow-left-Wr2ArHqT.js";import"./index-CDCVJ27y.js";import"./HomeIcon-Bwa0RWku.js";import"./HeartIcon-Bhv1WKZe.js";import"./ClockIcon-DyG5oHM7.js";import"./ChatBubbleLeftRightIcon-CAEhk58b.js";import"./EnvelopeIcon-BZtsCPZn.js";import"./DocumentTextIcon-DXVsqaBn.js";const w={draft:{label:"Brouillon",color:"bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"},active:{label:"Actif",color:"bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"},deprecated:{label:"Obsolète",color:"bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"}};function ee({workflows:c}){const i=c?.data||[],[a,o]=u.useState(null),m=()=>{a&&n.delete(route("workflows.destroy",a.uuid),{onSuccess:()=>{t.success("Workflow supprimé avec succès"),o(null)},onError:()=>{t.error("Erreur lors de la suppression")}})},p=r=>{n.post(route("workflows.duplicate",r.uuid),{},{onSuccess:()=>{t.success("Workflow dupliqué avec succès")},onError:()=>{t.error("Erreur lors de la duplication")}})},x=r=>{n.post(route("workflows.activate",r.uuid),{},{onSuccess:()=>{t.success("Workflow activé avec succès")},onError:()=>{t.error("Erreur lors de l'activation")}})};return e.jsxs(h,{children:[e.jsx(g,{title:"Workflows"}),e.jsx("div",{className:"py-6",children:e.jsxs("div",{className:"max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",children:[e.jsxs("div",{className:"flex items-center justify-between mb-6",children:[e.jsxs("div",{children:[e.jsx("h1",{className:"text-2xl font-bold text-gray-900 dark:text-white",children:"Workflows"}),e.jsx("p",{className:"text-sm text-gray-500 dark:text-gray-400 mt-1",children:"Gérez vos workflows et automatisations"})]}),e.jsxs(s,{href:route("workflows.create"),className:`
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
                                                `,title:"Supprimer",children:e.jsx(k,{className:"h-5 w-5"})})]})]},r.uuid)})})]})}),e.jsx(f,{open:!!a,onOpenChange:r=>!r&&o(null),onConfirm:m,title:"Supprimer le workflow",description:`Êtes-vous sûr de vouloir supprimer le workflow "${a?.name}" ? Cette action est irréversible.`})]})}export{ee as default};
