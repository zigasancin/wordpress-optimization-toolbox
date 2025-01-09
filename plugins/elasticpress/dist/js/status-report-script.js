!function(){"use strict";var e=window.wp.element,t=window.wp.i18n,a=window.wp.components,l=window.wp.data,n=window.wp.notices;const c=(0,e.createContext)(),{Fill:r,Slot:s}=(0,a.createSlotFill)("SettingsPageAction"),i=({children:t,title:i})=>{const{createNotice:o,removeNotice:p}=(0,l.useDispatch)(n.store),{notices:m}=(0,l.useSelect)((e=>({notices:e(n.store).getNotices()})),[]),u=(0,e.useMemo)((()=>({ActionSlot:r,createNotice:o,removeNotice:p})),[o,p]);return React.createElement(a.SlotFillProvider,null,React.createElement(c.Provider,{value:u},React.createElement("div",{className:"ep-settings-page"},React.createElement("div",{className:"ep-settings-page__wrap"},React.createElement("header",{className:"ep-settings-page__header"},React.createElement("h1",{className:"ep-settings-page__title"},i),React.createElement(s,null)),t),React.createElement(a.SnackbarList,{className:"ep-settings-page__snackbar-list",notices:m,onRemove:e=>p(e)}))))},{plainTextReport:o,reports:p}=window.epStatusReport;var m=window.wp.compose,u=window.wp.dom,d=window.wp.htmlEntities,R=({value:t})=>{if("object"===typeof t){const e=JSON.stringify(t,null,2);return React.createElement("pre",null,e)}if("string"===typeof t){if(0===t.indexOf("{"))try{const e=JSON.parse(t),a=JSON.stringify(e,null,2);return React.createElement("pre",null,a)}catch(e){return React.createElement("pre",null,t)}return React.createElement(e.RawHTML,null,t)}return t.toString()},E=({actions:t,groups:l,id:n,messages:c,title:r})=>l.length<1?null:React.createElement(a.Panel,{id:r,className:"ep-status-report"},React.createElement(a.PanelHeader,null,React.createElement("h2",{id:n},r),t.map((({href:e,label:t})=>React.createElement(a.Button,{href:(0,d.decodeEntities)(e),isDestructive:!0,isSecondary:!0,isSmall:!0,key:e},t)))),c.map((({message:t,type:l})=>React.createElement(a.Notice,{status:l,isDismissible:!1},React.createElement(e.RawHTML,null,(0,u.safeHTML)(t))))),l.map((({fields:e,title:t})=>React.createElement(a.PanelBody,{key:t,title:(0,d.decodeEntities)(t),initialOpen:!1},React.createElement("table",{cellPadding:"0",cellSpacing:"0",className:"wp-list-table widefat striped"},React.createElement("colgroup",null,React.createElement("col",null),React.createElement("col",null)),React.createElement("tbody",null,Object.entries(e).map((([e,{description:t="",label:a,value:l}])=>React.createElement("tr",{key:e},React.createElement("td",null,a,t?React.createElement("small",null,t):null),React.createElement("td",null,React.createElement(R,{value:l}))))))))))),g=({plainTextReport:l,reports:n})=>{const{createNotice:r}=(0,e.useContext)(c),s=`data:text/plain;charset=utf-8,${encodeURIComponent(l)}`,i=(0,m.useCopyToClipboard)(l,(()=>{r("info",(0,t.__)("Copied status report to clipboard.","elasticpress"))}));return React.createElement(React.Fragment,null,React.createElement("p",null,(0,t.__)("This screen provides a list of information related to ElasticPress and synced content that can be helpful during troubleshooting. This list can also be copy/pasted and shared as needed.","elasticpress")),React.createElement("p",null,React.createElement(a.Flex,{justify:"start"},React.createElement(a.FlexItem,null,React.createElement(a.Button,{download:"elasticpress-report.txt",href:s,variant:"primary"},(0,t.__)("Download status report","elasticpress"))),React.createElement(a.FlexItem,null,React.createElement(a.Button,{ref:i,variant:"secondary"},(0,t.__)("Copy status report to clipboard","elasticpress"))))),Object.entries(n).map((([e,{actions:t,groups:a,messages:l,title:n}])=>React.createElement(E,{actions:t,groups:a,id:e,key:e,messages:l,title:n}))))};const w=()=>React.createElement(i,{title:(0,t.__)("Status Report","elasticpress")},React.createElement(g,{plainTextReport:o,reports:p}));if("function"===typeof e.createRoot){(0,e.createRoot)(document.getElementById("ep-status-reports")).render(React.createElement(w,null))}else(0,e.render)(React.createElement(w,null),document.getElementById("ep-status-reports"))}();