!function(){"use strict";var e={5251:function(e,t,s){var n=s(9196),r=60103;if(t.Fragment=60107,"function"===typeof Symbol&&Symbol.for){var a=Symbol.for;r=a("react.element"),t.Fragment=a("react.fragment")}var i=n.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,o=Object.prototype.hasOwnProperty,l={key:!0,ref:!0,__self:!0,__source:!0};function d(e,t,s){var n,a={},d=null,c=null;for(n in void 0!==s&&(d=""+s),void 0!==t.key&&(d=""+t.key),void 0!==t.ref&&(c=t.ref),t)o.call(t,n)&&!l.hasOwnProperty(n)&&(a[n]=t[n]);if(e&&e.defaultProps)for(n in t=e.defaultProps)void 0===a[n]&&(a[n]=t[n]);return{$$typeof:r,type:e,key:d,ref:c,props:a,_owner:i.current}}t.jsx=d,t.jsxs=d},5893:function(e,t,s){e.exports=s(5251)},9196:function(e){e.exports=window.React}},t={};function s(n){var r=t[n];if(void 0!==r)return r.exports;var a=t[n]={exports:{}};return e[n](a,a.exports,s),a.exports}!function(){var e=window.wp.element;var t={randomUUID:"undefined"!==typeof crypto&&crypto.randomUUID&&crypto.randomUUID.bind(crypto)};let n;const r=new Uint8Array(16);function a(){if(!n&&(n="undefined"!==typeof crypto&&crypto.getRandomValues&&crypto.getRandomValues.bind(crypto),!n))throw new Error("crypto.getRandomValues() not supported. See https://github.com/uuidjs/uuid#getrandomvalues-not-supported");return n(r)}const i=[];for(let e=0;e<256;++e)i.push((e+256).toString(16).slice(1));function o(e,t=0){return(i[e[t+0]]+i[e[t+1]]+i[e[t+2]]+i[e[t+3]]+"-"+i[e[t+4]]+i[e[t+5]]+"-"+i[e[t+6]]+i[e[t+7]]+"-"+i[e[t+8]]+i[e[t+9]]+"-"+i[e[t+10]]+i[e[t+11]]+i[e[t+12]]+i[e[t+13]]+i[e[t+14]]+i[e[t+15]]).toLowerCase()}var l=function(e,s,n){if(t.randomUUID&&!s&&!e)return t.randomUUID();const r=(e=e||{}).random||(e.rng||a)();if(r[6]=15&r[6]|64,r[8]=63&r[8]|128,s){n=n||0;for(let e=0;e<16;++e)s[n+e]=r[e];return s}return o(r)};const d=(e=[],t="")=>({id:t.length?t:l(),synonyms:e,valid:!0}),c=({sets:e,alternatives:t})=>{const s=[];return s.push("# Defined sets ( equivalent synonyms)."),s.push(...e.map((({synonyms:e})=>e.map((({value:e})=>e)).join(", ")))),s.push("\r"),s.push("# Defined alternatives (explicit mappings)."),s.push(...t.map((e=>!!e.synonyms.find((e=>e.primary&&e.value.length))&&e.synonyms.find((e=>e.primary)).value.concat(" => ").concat(e.synonyms.filter((e=>!e.primary)).map((({value:e})=>e)).join(", "))))),s.filter(Boolean).join("\n")},m=(e,t)=>{const s=(e,t=!1)=>({label:e,value:e,primary:t});return{...t,...e.split(/\r?\n/).reduce(((e,t)=>{if(0===t.indexOf("#")||!t.trim().length)return e;if(-1!==t.indexOf("=>")){const n=t.split("=>");return{...e,alternatives:[...e.alternatives,d([s(n[0].trim(),!0),...n[1].split(",").filter((e=>e.trim())).map((e=>s(e.trim())))])]}}return{...e,sets:[...e.sets,d([...t.split(",").filter((e=>e.trim())).map((e=>s(e.trim())))])]}}),{alternatives:[],sets:[]})}},{alternatives:y,sets:u,initialMode:p}=window.epSynonyms.data,v=u?u.map(d):[d()],x=y?y.map(d):[d()],h={isSolrEditable:"advanced"===p,isSolrVisible:"advanced"===p,alternatives:x,sets:v,solr:c({sets:v,alternatives:x}),dirty:!1,submit:!1},E=(e,t)=>{switch(t.type){case"ADD_SET":return{...e,sets:[...e.sets,d()],dirty:!0};case"UPDATE_SET":return{...e,sets:e.sets.map((e=>e.id!==t.data.id?e:d(t.data.tokens,t.data.id))),dirty:!0};case"REMOVE_SET":return{...e,sets:e.sets.filter((({id:e})=>e!==t.data)),dirty:!0};case"ADD_ALTERNATIVE":return{...e,alternatives:[...e.alternatives,d()],dirty:!0};case"UPDATE_ALTERNATIVE":return{...e,alternatives:[...e.alternatives.map((e=>e.id!==t.data.id?e:d([...t.data.tokens,...e.synonyms.filter((e=>e.primary))],t.data.id)))],dirty:!0};case"UPDATE_ALTERNATIVE_PRIMARY":return{...e,alternatives:[...e.alternatives.map((e=>e.id!==t.data.id?e:d([t.data.token,...e.synonyms.filter((e=>!e.primary))],t.data.id)))],dirty:!0};case"REMOVE_ALTERNATIVE":return{...e,alternatives:e.alternatives.filter((({id:e})=>e!==t.data)),dirty:!0};case"SET_SOLR_EDITABLE":return{...e,isSolrEditable:!!t.data,isSolrVisible:!!t.data};case"UPDATE_SOLR":return{...e,solr:t.data,dirty:!0};case"REDUCE_SOLR_TO_STATE":return{...m(e.solr,e),dirty:!0};case"REDUCE_STATE_TO_SOLR":return{...e,solr:c(e)};case"VALIDATE_ALL":return{...e,sets:e.sets.map((e=>({...e,valid:e.synonyms.length>1}))),alternatives:e.alternatives.map((e=>({...e,valid:e.synonyms.length>1&&!!e.synonyms.filter((({primary:e,value:t})=>e&&t.length)).length}))),dirty:!1};case"SUBMIT":return{...e,submit:!0};default:return e}};var _=s(5893);const f=(0,e.createContext)(),T=(0,e.createContext)(),j=t=>{const{children:s}=t,[n,r]=(0,e.useReducer)(E,h);return(0,_.jsx)(f.Provider,{value:n,children:(0,_.jsx)(T.Provider,{value:r,children:s})})};var g=window.wp.components;var A=({id:t,synonyms:s,removeAction:n,updateAction:r})=>{const a=(0,e.useContext)(T),{removeItemText:i}=window.epSynonyms.i18n;return(0,_.jsxs)(_.Fragment,{children:[(0,_.jsx)(g.FormTokenField,{label:null,onChange:e=>{const s=e.map((e=>({label:e,value:e,primary:!1})));a({type:r,data:{id:t,tokens:s}})},value:s.map((e=>e.value))},t),(0,_.jsxs)("button",{className:"synonym__remove",type:"button",onClick:()=>{a({type:n,data:t})},children:[(0,_.jsx)("span",{className:"dashicons dashicons-dismiss"}),(0,_.jsx)("span",{children:i})]})]})};var S=t=>{const{id:s,synonyms:n,removeAction:r,updateAction:a}=t,i=n.find((e=>e.primary)),[o,l]=(0,e.useState)(i?i.value:""),d=(0,e.useContext)(T),c=(0,e.useRef)(null);(0,e.useEffect)((()=>{var e;d({type:"UPDATE_ALTERNATIVE_PRIMARY",data:{id:s,token:(e=o,{label:e,value:e,primary:!0})}})}),[o,s,d]),(0,e.useEffect)((()=>{c.current.focus()}),[c]);const m=(0,e.useMemo)((()=>n.filter((e=>!e.primary))),[n]);return(0,_.jsxs)(_.Fragment,{children:[(0,_.jsx)("input",{type:"text",className:"ep-synonyms__input",onChange:e=>l(e.target.value),value:o,onKeyDown:e=>{if("Enter"===e.key)e.preventDefault()},ref:c}),(0,_.jsx)(A,{id:s,updateAction:a,removeAction:r,synonyms:m})]})};var b=({alternatives:t})=>{const s=(0,e.useContext)(T),n=(0,e.useContext)(f),{alternativesInputHeading:r,alternativesPrimaryHeading:a,alternativesAddButtonText:i,alternativesErrorMessage:o}=window.epSynonyms.i18n;return(0,_.jsx)("div",{className:"synonym-alternatives-editor metabox-holder",children:(0,_.jsxs)("div",{className:"postbox",children:[(0,_.jsxs)("h2",{className:"hndle",children:[(0,_.jsx)("span",{className:"synonym-alternatives__primary-heading",children:a}),(0,_.jsx)("span",{className:"synonym-alternatives__input-heading",children:r})]}),(0,_.jsxs)("div",{className:"inside",children:[t.map((t=>(0,_.jsxs)(e.Fragment,{children:[(0,_.jsx)("div",{className:"synonym-alternative-editor",children:(0,_.jsx)(S,{...t,updateAction:"UPDATE_ALTERNATIVE",removeAction:"REMOVE_ALTERNATIVE"})}),!t.valid&&(0,_.jsx)("p",{className:"synonym__validation",children:o})]},t.id))),(0,_.jsx)("button",{type:"button",className:"button button-secondary",onClick:e=>{const[r]=n.alternatives.slice(-1);t.length&&!r.synonyms.filter((({value:e})=>e.length)).length||s({type:"ADD_ALTERNATIVE"}),e.preventDefault()},children:i})]})]})})};var N=({sets:t})=>{const s=(0,e.useContext)(T),n=(0,e.useContext)(f),{setsInputHeading:r,setsAddButtonText:a,setsErrorMessage:i}=window.epSynonyms.i18n;return(0,_.jsx)("div",{className:"synonym-sets-editor metabox-holder",children:(0,_.jsxs)("div",{className:"postbox",children:[(0,_.jsx)("h2",{className:"hndle",children:(0,_.jsx)("span",{children:r})}),(0,_.jsxs)("div",{className:"inside",children:[t.map((t=>(0,_.jsxs)(e.Fragment,{children:[(0,_.jsx)("div",{className:"synonym-set-editor",children:(0,_.jsx)(A,{...t,updateAction:"UPDATE_SET",removeAction:"REMOVE_SET"})}),!t.valid&&(0,_.jsx)("p",{className:"synonym__validation",children:i})]},t.id))),(0,_.jsx)("button",{type:"button",className:"button button-secondary",onClick:e=>{const[r]=n.sets.slice(-1);t.length&&!r.synonyms.length||s({type:"ADD_SET"}),e.preventDefault()},children:a})]})]})})};var D=()=>{const t=(0,e.useContext)(f),s=(0,e.useContext)(T),{alternatives:n,isSolrEditable:r,isSolrVisible:a,sets:i,solr:o}=t,{synonymsTextareaInputName:l,solrInputHeading:d,solrAlternativesErrorMessage:c,solrSetsErrorMessage:m}=window.epSynonyms.i18n;return(0,_.jsx)("div",{className:"synonym-solr-editor metabox-holder "+(a?"":"hidden"),children:(0,_.jsxs)("div",{className:"postbox",children:[(0,_.jsx)("h2",{className:"hndle",children:(0,_.jsx)("span",{children:d})}),(0,_.jsxs)("div",{className:"inside",children:[(0,_.jsx)("textarea",{className:"large-text",id:"ep-synonym-input",name:l,rows:"20",value:o,readOnly:!r,onChange:e=>s({type:"UPDATE_SOLR",data:e.target.value})}),(0,_.jsxs)("div",{role:"region","aria-live":"assertive",className:"synonym-solr-editor__validation",children:[n.some((e=>!e.valid))&&(0,_.jsx)("p",{children:c}),i.some((e=>!e.valid))&&(0,_.jsx)("p",{children:m})]})]})]})})};var R=()=>{const t=(0,e.useContext)(f),s=(0,e.useContext)(T),{alternatives:n,sets:r,isSolrEditable:a,isSolrVisible:i,dirty:o,submit:l}=t,{pageHeading:d,pageDescription:c,pageToggleAdvanceText:m,pageToggleSimpleText:y,alternativesTitle:u,alternativesDescription:p,setsTitle:v,setsDescription:x,solrTitle:h,solrDescription:E,submitText:j}=window.epSynonyms.i18n;return(0,e.useEffect)((()=>{var e;l&&!o&&[...(e=t).sets,...e.alternatives].reduce(((e,t)=>e?t.valid:e),!0)&&document.querySelector(".wrap form").submit()}),[l,o,t]),(0,_.jsxs)(_.Fragment,{children:[(0,_.jsxs)("h1",{className:"wp-heading-inline",children:[d," ",(0,_.jsx)("button",{onClick:()=>{s(a?{type:"REDUCE_SOLR_TO_STATE"}:{type:"REDUCE_STATE_TO_SOLR"}),s({type:"SET_SOLR_EDITABLE",data:!a})},type:"button",className:"page-title-action",children:a?y:m})]}),(0,_.jsx)("p",{children:c}),!a&&(0,_.jsxs)(_.Fragment,{children:[(0,_.jsxs)("div",{className:"synonym-editor synonym-editor__sets",children:[(0,_.jsx)("h2",{children:`${v} (${r.length})`}),(0,_.jsx)("p",{children:x}),(0,_.jsx)(N,{sets:r})]}),(0,_.jsxs)("div",{className:"synonym-editor synonym-editor__alteratives",children:[(0,_.jsx)("h2",{children:`${u} (${n.length})`}),(0,_.jsx)("p",{children:p}),(0,_.jsx)(b,{alternatives:n})]})]}),(0,_.jsxs)("div",{className:"synonym-editor synonym-editor__solr",children:[i&&(0,_.jsx)("h2",{children:h}),i&&(0,_.jsx)("p",{children:E}),(0,_.jsx)(D,{})]}),(0,_.jsx)("input",{type:"hidden",name:"synonyms_editor_mode",value:a?"advanced":"simple"}),(0,_.jsx)("div",{className:"synonym-btn-group",children:(0,_.jsx)("button",{onClick:()=>{a&&s({type:"REDUCE_SOLR_TO_STATE"}),s({type:"VALIDATE_ALL"}),s({type:"REDUCE_STATE_TO_SOLR"}),s({type:"SUBMIT"})},type:"button",className:"button button-primary",children:j})})]})};(0,e.render)((0,_.jsx)(j,{children:(0,_.jsx)(R,{})}),document.querySelector("#synonym-root")||!1)}()}();