!function(){"use strict";var e={5251:function(e,t,n){var s=n(9196),i=60103;if(t.Fragment=60107,"function"===typeof Symbol&&Symbol.for){var a=Symbol.for;i=a("react.element"),t.Fragment=a("react.fragment")}var r=s.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,l=Object.prototype.hasOwnProperty,o={key:!0,ref:!0,__self:!0,__source:!0};function c(e,t,n){var s,a={},c=null,d=null;for(s in void 0!==n&&(c=""+n),void 0!==t.key&&(c=""+t.key),void 0!==t.ref&&(d=t.ref),t)l.call(t,s)&&!o.hasOwnProperty(s)&&(a[s]=t[s]);if(e&&e.defaultProps)for(s in t=e.defaultProps)void 0===a[s]&&(a[s]=t[s]);return{$$typeof:i,type:e,key:c,ref:d,props:a,_owner:r.current}}t.jsx=c,t.jsxs=c},5893:function(e,t,n){e.exports=n(5251)},9196:function(e){e.exports=window.React}},t={};function n(s){var i=t[s];if(void 0!==i)return i.exports;var a=t[s]={exports:{}};return e[s](a,a.exports,n),a.exports}n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,{a:t}),t},n.d=function(e,t){for(var s in t)n.o(t,s)&&!n.o(e,s)&&Object.defineProperty(e,s,{enumerable:!0,get:t[s]})},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){var e=window.wp.element,t=window.wp.i18n,s=window.wp.components,i=window.wp.data,a=window.wp.notices,r=n(5893);const l=(0,e.createContext)(),o=({children:t,title:n})=>{const{createNotice:o,removeNotice:c}=(0,i.useDispatch)(a.store),{notices:d}=(0,i.useSelect)((e=>({notices:e(a.store).getNotices()})),[]),h=(0,e.useMemo)((()=>({createNotice:o,removeNotice:c})),[o,c]);return(0,r.jsx)(l.Provider,{value:h,children:(0,r.jsxs)("div",{className:"ep-settings-page",children:[(0,r.jsxs)("div",{className:"ep-settings-page__wrap",children:[(0,r.jsx)("h1",{className:"ep-settings-page__title",children:n}),t]}),(0,r.jsx)(s.SnackbarList,{className:"ep-settings-page__snackbar-list",notices:d,onRemove:e=>c(e)})]})})},c=()=>(0,e.useContext)(l),{apiUrl:d,metaMode:h,syncUrl:p,weightableFields:g,weightingConfiguration:u}=window.epWeighting;var w=window.wp.apiFetch,m=n.n(w);const f=(0,e.createContext)(),y=({apiUrl:t,children:n,metaMode:s,weightableFields:i,weightingConfiguration:a})=>{const[l,o]=(0,e.useState)({...a}),[c,d]=(0,e.useState)(!1),h=(0,e.useMemo)((()=>"manual"===s),[s]),p={currentWeightingConfiguration:l,isBusy:c,isManual:h,save:async()=>{d(!0);try{await m()({body:JSON.stringify(l),headers:{"Content-Type":"application/json"},method:"POST",url:t})}catch(e){throw console.error(e),e}finally{d(!1)}},setWeightingForPostType:(e,t)=>{o({...l,[e]:t})},weightableFields:i};return(0,r.jsx)(f.Provider,{value:p,children:n})},_=()=>(0,e.useContext)(f);var x=n(9196),v=window.wp.primitives;var b=(0,x.createElement)(v.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},(0,x.createElement)(v.Path,{d:"M20 5h-5.7c0-1.3-1-2.3-2.3-2.3S9.7 3.7 9.7 5H4v2h1.5v.3l1.7 11.1c.1 1 1 1.7 2 1.7h5.7c1 0 1.8-.7 2-1.7l1.7-11.1V7H20V5zm-3.2 2l-1.7 11.1c0 .1-.1.2-.3.2H9.1c-.1 0-.3-.1-.3-.2L7.2 7h9.6z"})),j=({label:e,onChange:n,onDelete:i,value:a})=>{const{enabled:l=!1,weight:o=0}=a;return(0,r.jsx)("div",{className:"ep-weighting-field",children:(0,r.jsxs)("fieldset",{children:[(0,r.jsx)("legend",{className:"ep-weighting-field__name",children:e}),(0,r.jsx)("div",{className:"ep-weighting-field__searchable",children:(0,r.jsx)(s.CheckboxControl,{checked:l,label:(0,t.__)("Searchable","elasticpress"),onChange:e=>{n({weight:o,enabled:e})}})}),(0,r.jsx)("div",{className:"ep-weighting-field__weighting",children:(0,r.jsx)(s.RangeControl,{disabled:!l,label:(0,t.__)("Weight","elasticpress"),max:100,min:1,onChange:e=>{n({enabled:!0,weight:e})},value:o})}),(0,r.jsx)("div",{className:"ep-weighting-field__actions",children:(0,r.jsx)(s.Button,{className:"ep-weighting-action ep-weighting-action--delete",disabled:!i,icon:b,label:(0,t.__)("Remove","elasticpress"),onClick:i})})]})})},k=({group:n,postType:i})=>{const{createNotice:a}=c(),{currentWeightingConfiguration:l,setWeightingForPostType:o,weightableFields:d}=_(),[h,p]=(0,e.useState)(""),g=l[i],{fields:u}=d.find((e=>e.key===i)),w="ep_metadata"===n,m=(0,e.useMemo)((()=>u.filter((e=>e.group===n))),[u,n]),f=(0,e.useMemo)((()=>{if(!w)return[];const e=u.map((({key:e})=>e));return Object.keys(g).reduce(((t,n)=>{if(e.includes(n))return t;const s=n.match(/meta\.(?<label>.*)\.value/);if(!s)return t;const{label:i}=s.groups;return t.push({key:n,label:i}),t}),[])}),[u,w,g]),y=(e,t)=>{o(i,{...g,[t]:e})},x=()=>{const e=`meta.${h}.value`,n=m.some((t=>t.key===e)),s=f.some((t=>t.key===e));if(n||s)return void a("info",(0,t.sprintf)((0,t.__)("%s is already being synced.","elasticpress"),h));const r={...g,[e]:{enabled:!1,weight:0}};o(i,r),p("")};return(0,r.jsxs)(r.Fragment,{children:[m.map((({key:e,label:t})=>(0,r.jsx)(s.PanelRow,{children:(0,r.jsx)(j,{label:t,value:g[e]||{},onChange:t=>{y(t,e)}})},e))),f.map((({key:e,label:t})=>(0,r.jsx)(s.PanelRow,{children:(0,r.jsx)(j,{label:t,value:g[e]||{},onChange:t=>{y(t,e)},onDelete:()=>{(e=>{const t={...g};delete t[e],o(i,t)})(e)}})},e))),w?(0,r.jsxs)(s.PanelRow,{className:"ep-weighting-add-new",children:[(0,r.jsx)(s.TextControl,{help:(0,t.__)("Make sure to Sync after adding new fields to ensure that the fields are synced for any existing content that uses them.","elasticpress"),label:(0,t.__)("Add field","elasticpress"),onChange:e=>p(e),onKeyDown:e=>{"Enter"===e.key&&(e.preventDefault(),x())},placeholder:(0,t.__)("Metadata key","elasticpress"),value:h}),(0,r.jsx)(s.Button,{disabled:!h,isSecondary:!0,onClick:x,variant:"secondary",children:(0,t.__)("Add","elasticpress")})]}):null]})},C=({postType:e})=>{const{isManual:t,weightableFields:n}=_(),{label:i,groups:a}=n.find((t=>t.key===e));return(0,r.jsxs)(s.Panel,{className:"ep-weighting-post-type",children:[(0,r.jsx)(s.PanelHeader,{children:(0,r.jsx)("h2",{children:i})}),a.map((({key:n,label:i})=>{const a="ep_metadata"===n;return!a||t?(0,r.jsx)(s.PanelBody,{initialOpen:!a,title:i,children:(0,r.jsx)(k,{group:n,label:i,postType:e})},n):null}))]})},N=()=>{const{createNotice:e}=c(),{isBusy:n,save:i,weightableFields:a}=_();return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)("p",{children:(0,t.__)("This dashboard enables you to select which fields ElasticPress should sync, whether to use those fields in searches, and how heavily to weight fields in the search algorithm. In general, increasing the Weight of a field will increase the relevancy score of a post that has matching text in that field.","elasticpress")}),(0,r.jsx)("p",{children:(0,t.__)("For example, adding more weight to the title attribute will cause search matches on the post title to appear more prominently.","elasticpress")}),(0,r.jsxs)("form",{className:"ep-weighting-screen",onSubmit:async n=>{n.preventDefault();try{await i(),e("success",(0,t.__)("Settings saved.","elasticpress"))}catch(n){e("error",(0,t.__)("Something went wrong. Please try again.","elasticpress"))}},children:[a.map((({key:e})=>(0,r.jsx)(C,{postType:e},e))),(0,r.jsx)(s.Button,{disabled:n,isBusy:n,isPrimary:!0,type:"submit",variant:"primary",children:(0,t.__)("Save changes","elasticpress")})]})]})};const S=()=>(0,r.jsx)(o,{title:(0,t.__)("Manage Search Fields & Weighting","elasticpress"),children:(0,r.jsx)(y,{apiUrl:d,metaMode:h,syncUrl:p,weightingConfiguration:u,weightableFields:g,children:(0,r.jsx)(N,{})})});if("function"===typeof e.createRoot){(0,e.createRoot)(document.getElementById("ep-weighting-screen")).render((0,r.jsx)(S,{}))}else(0,e.render)((0,r.jsx)(S,{}),document.getElementById("ep-weighting-screen"))}()}();