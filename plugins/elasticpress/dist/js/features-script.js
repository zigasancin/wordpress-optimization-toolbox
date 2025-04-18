!function(){"use strict";var e={n:function(t){var a=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(a,{a:a}),a},d:function(t,a){for(var s in a)e.o(a,s)&&!e.o(t,s)&&Object.defineProperty(t,s,{enumerable:!0,get:a[s]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)}},t=window.wp.element,a=window.wp.i18n,s=window.wp.components,n=window.wp.data,r=window.wp.notices;const l=(0,t.createContext)(),{Fill:c,Slot:i}=(0,s.createSlotFill)("SettingsPageAction"),o=({children:e,title:a})=>{const{createNotice:o,removeNotice:u}=(0,n.useDispatch)(r.store),{notices:d}=(0,n.useSelect)((e=>({notices:e(r.store).getNotices()})),[]),m=(0,t.useMemo)((()=>({ActionSlot:c,createNotice:o,removeNotice:u})),[o,u]);return React.createElement(s.SlotFillProvider,null,React.createElement(l.Provider,{value:m},React.createElement("div",{className:"ep-settings-page"},React.createElement("div",{className:"ep-settings-page__wrap"},React.createElement("header",{className:"ep-settings-page__header"},React.createElement("h1",{className:"ep-settings-page__title"},a),React.createElement(i,null)),e),React.createElement(s.SnackbarList,{className:"ep-settings-page__snackbar-list",notices:d,onRemove:e=>u(e)}))))},{apiUrl:u,epioLogoUrl:d,features:m,indexMeta:g,settings:p,settingsDraft:y,syncUrl:_,syncNonce:h}=window.epDashboard;var f=window.wp.apiFetch,b=e.n(f),R=window.lodash;const E=(0,t.createContext)(),S=({apiUrl:e,children:a,defaultSettings:s,epioLogoUrl:n,features:r,indexMeta:l,syncedSettings:c})=>{const[i,o]=(0,t.useState)(!1),[u,d]=(0,t.useState)(!!l),[m,g]=(0,t.useState)({...s}),[p,y]=(0,t.useState)({...c}),_=(0,t.useCallback)((e=>r.find((t=>t.slug===e))),[r]),h=(0,t.useMemo)((()=>{const e=e=>{Object.keys(e).forEach((t=>{delete e[t].force_inactive}))};return e(m),e(p),!(0,R.isEqual)(m,p)}),[m,p]),f=(0,t.useCallback)(((e,t,a)=>a&&e&&"0"!==e&&e!==t),[]),S=(0,t.useCallback)((e=>{const{slug:t,settingsSchema:a}=e;return a.some((e=>{if(!0!==m?.[t]?.active)return!1;const a=e.requires_sync,s=m?.[t]?.[e.key],n=c?.[t]?.[e.key];return f(s,n,a)}))}),[m,c,f]),v=(0,t.useMemo)((()=>r.reduce(((e,t)=>(S(t)&&e.push(t.slug),e)),[])),[r,S]),w=(0,t.useMemo)((()=>!!v.length),[v]),x={epioLogoUrl:n,features:r,featuresRequiringSync:v,getFeature:_,isBusy:i,isModified:h,isSyncing:u,setIsSyncing:d,isSyncRequired:w,resetSettings:()=>{g({...p})},saveSettings:async()=>{try{o(!0);const t=await b()({body:JSON.stringify(m),headers:{"Content-Type":"application/json"},method:"PUT",url:e});y(t.data)}finally{o(!1)}},savedSettings:p,syncedSettings:c,settings:m,setSettings:g,willSettingRequireSync:f};return React.createElement(E.Provider,{value:x},a)},v=()=>(0,t.useContext)(E);var w=window.wp.dom,x=({disabled:e,help:n,label:r,name:l,onChange:c,options:i,requiresFeature:o,requiresSync:u,syncedValue:d,type:m,value:g})=>{const{getFeature:p,isBusy:y,settings:_,willSettingRequireSync:h}=v(),f=n?React.createElement("span",{dangerouslySetInnerHTML:{__html:(0,w.safeHTML)(n)}}):null,b=i?i.map((e=>({value:e.value,label:React.createElement("span",{dangerouslySetInnerHTML:{__html:(0,w.safeHTML)(e.label)}})}))):[],R=!(!o||!0===_[o]?.active)&&p(o),E="active"===l?(0,a.__)("The %s feature must be enabled to use this feature.","elasticpress"):(0,a.__)("The %s feature must be enabled to use the following setting.","elasticpress"),S="active"===l?(0,a.__)("Enabling this feature requires re-syncing your content.","elasticpress"):(0,a.__)("A change to following setting requires re-syncing your content.","elasticpress"),x=y||e||R,C=h(g,d,u),M=e=>{c(e?"1":"0")},N=e=>{const t=e.map((e=>i.find((t=>t.label===e))?.value)).filter(Boolean).join(",");c(t)};return React.createElement(React.Fragment,null,R?React.createElement(s.Notice,{isDismissible:!1,status:"active"===l?"error":"warning"},(0,a.sprintf)(E,R.shortTitle)):null,C?React.createElement(s.Notice,{isDismissible:!1,status:"warning"},S):null,React.createElement("div",{className:"ep-dashboard-control"},(()=>{switch(m){case"checkbox":return React.createElement(s.CheckboxControl,{checked:"1"===g,help:f,label:r,onChange:M,disabled:x,__nextHasNoMarginBottom:!0});case"hidden":return null;case"markup":return React.createElement(t.RawHTML,null,(0,w.safeHTML)(r));case"multiple":{const e=i.map((e=>e.label)),t=g.split(",").map((e=>i.find((t=>t.value===e))?.label)).filter(Boolean);return React.createElement(s.FormTokenField,{__experimentalExpandOnFocus:!0,__experimentalShowHowTo:!1,label:r,onChange:N,disabled:x,suggestions:e,value:t,__nextHasNoMarginBottom:!0,__next40pxDefaultSize:!0})}case"radio":return React.createElement(s.RadioControl,{help:f,label:r,onChange:c,options:b,disabled:x,selected:g});case"select":return React.createElement(s.SelectControl,{help:f,label:r,onChange:c,options:i,disabled:x,value:g,__nextHasNoMarginBottom:!0,__next40pxDefaultSize:!0});case"toggle":return React.createElement(s.ToggleControl,{checked:g,help:f,label:r,onChange:c,disabled:x,__nextHasNoMarginBottom:!0});case"textarea":return React.createElement(s.TextareaControl,{help:f,label:r,onChange:c,disabled:x,value:g,__nextHasNoMarginBottom:!0});default:return React.createElement(s.TextControl,{help:f,label:r,onChange:c,disabled:x,value:g,type:m,__nextHasNoMarginBottom:!0,__next40pxDefaultSize:!0})}})()))},C=({feature:e,settingsSchema:t})=>{const{getFeature:a,settings:s,setSettings:n,syncedSettings:r}=v(),{isAvailable:l}=a(e);return t.map((t=>{const{default:a,disabled:c,help:i,key:o,label:u,options:d,requires_feature:m,requires_sync:g,type:p}=t;let y="undefined"!==typeof s[e]?.[o]?s[e][o]:a;return"active"!==o||l||(y=!1),React.createElement(x,{disabled:c||!l,key:o,help:i,label:u,name:o,onChange:t=>((t,a)=>{n({...s,[e]:{...s[e],[t]:a}})})(o,t),options:d,syncedValue:r?.[e]?.[o],requiresFeature:m,requiresSync:g,type:p,value:y})}))},M=({feature:e})=>{const{epioLogoUrl:n,getFeature:r}=v(),{isPoweredByEpio:l,reqStatusCode:c,reqStatusMessages:i,settingsSchema:o,summary:u,title:d}=r(e);return React.createElement(React.Fragment,null,React.createElement("h3",{className:"ep-dashboard-heading"},React.createElement(t.RawHTML,null,(0,w.safeHTML)(d)),l?React.createElement("img",{alt:(0,a.__)("ElasticPress.io logo"),height:"20",src:n,width:"110"}):null),React.createElement("p",{dangerouslySetInnerHTML:{__html:(0,w.safeHTML)(u)}}),i.map(((e,t)=>React.createElement(s.Notice,{isDismissible:!1,key:t,status:2===c?"error":"warning"},React.createElement("span",{dangerouslySetInnerHTML:{__html:(0,w.safeHTML)(e)}})))),React.createElement(C,{feature:e,settingsSchema:o}))},N=({feature:e})=>{const{getFeature:t,featuresRequiringSync:s}=v(),{shortTitle:n,isAvailable:r}=t(e),l=r?"":(0,a.__)("Unavailable","elasticpress"),c=s.includes(e)?(0,a.__)("Sync required","elasticpress"):l;return React.createElement("div",{className:"ep-feature-tab"},n,c?React.createElement("small",{className:"ep-feature-tab__status"},c):null)},F=()=>{const{createNotice:e}=(0,t.useContext)(l),{features:n,isBusy:r,isModified:c,isSyncing:i,isSyncRequired:o,resetSettings:u,saveSettings:d,setIsSyncing:m}=v(),g=(0,t.useMemo)((()=>{const e=new URL(_);return e.searchParams.append("do_sync","features"),e.searchParams.append("ep_sync_nonce",h),e.toString()}),[]),p=(0,a.__)("Could not save feature settings. Please try again.","elasticpress"),y=[{url:_,label:(0,a.__)("View sync status","elasticpress")}],f=(0,a.__)("Cannot save settings while a sync is in progress.","elasticpress"),b=(0,a.__)("Changes to feature settings discarded.","elasticpress"),R=[{url:g,label:(0,a.__)("Sync","elasticpress")}],E=(0,a.__)("If you choose to sync later some settings changes may not take effect until the sync is performed. Save and sync later?","elasticpress"),S=(0,a.__)("Saving these settings will begin re-syncing your content. Save and sync now?","elasticpress"),w=(0,a.__)("Feature settings saved. Starting sync…","elasticpress"),x=(0,a.__)("Feature settings saved.","elasticpress"),[C,F]=(0,t.useState)(!1),T=n.filter((e=>e.isVisible)).map((e=>({name:e.slug,title:React.createElement(N,{feature:e.slug})}))),k=t=>{if("is_syncing"===t.data)return e("error",f,{actions:y}),void m(!0);const s=`${(0,a.__)("ElasticPress: Could not save feature settings.","elasticpress")}\n${t.message}`;console.error(s),e("error",p)};return React.createElement("form",{onReset:t=>{t.preventDefault(),u(),e("success",b)},onSubmit:async t=>{if(t.preventDefault(),!o||window.confirm(S)){F(!1);try{await d(),o?(e("success",w),window.location=g):e("success",x)}catch(e){k(e)}}}},React.createElement(s.Panel,{className:"ep-dashboard-panel"},React.createElement(s.PanelBody,null,i?React.createElement(s.Notice,{actions:y,isDismissible:!1,status:"warning"},f):null,React.createElement(s.TabPanel,{className:"ep-dashboard-tabs",orientation:"vertical",tabs:T},(({name:e})=>React.createElement(M,{feature:e,key:e}))))),React.createElement(s.Flex,{justify:"start"},React.createElement(s.FlexItem,null,React.createElement(s.Button,{disabled:r||i,isBusy:r&&!C,type:"submit",variant:"primary"},o?(0,a.__)("Save and sync now","elasticpress"):(0,a.__)("Save changes","elasticpress"))),o?React.createElement(s.FlexItem,null,React.createElement(s.Button,{disabled:r||i,isBusy:r&&C,onClick:async()=>{if(window.confirm(E)){F(!0);try{await d(!1),e("success",x,{actions:R})}catch(e){k(e)}}},type:"button",variant:"secondary"},(0,a.__)("Save and sync later","elasticpress"))):null,c?React.createElement(s.FlexItem,null,React.createElement(s.Button,{disabled:r,type:"reset",variant:"tertiary"},(0,a.__)("Discard changes","elasticpress"))):null))};const T=()=>React.createElement(o,{title:(0,a.__)("Features","elasticpress")},React.createElement(S,{apiUrl:u,defaultSettings:y||p,epioLogoUrl:d,features:m,indexMeta:g,syncedSettings:p,syncUrl:_},React.createElement("p",null,(0,t.createInterpolateElement)((0,a.__)("ElasticPress Features add functionality to enhance search and queries on your site. You may choose to activate some or all of these Features depending on your needs. You can learn more about each Feature <a>here</a>.","elasticpress"),{a:React.createElement("a",{target:"_blank",href:"https://www.elasticpress.io/documentation/article/configuring-elasticpress-via-the-plugin-dashboard/",rel:"noreferrer"})})),React.createElement(F,null)));if("function"===typeof t.createRoot){(0,t.createRoot)(document.getElementById("ep-dashboard")).render(React.createElement(T,null))}else(0,t.render)(React.createElement(T,null),document.getElementById("ep-dashboard"))}();