!function(){"use strict";var e=window.wp.element,t=window.wp.i18n,s=window.wp.hooks;var r={randomUUID:"undefined"!==typeof crypto&&crypto.randomUUID&&crypto.randomUUID.bind(crypto)};let a;const n=new Uint8Array(16);const o=[];for(let e=0;e<256;++e)o.push((e+256).toString(16).slice(1));function c(e,t=0){return(o[e[t+0]]+o[e[t+1]]+o[e[t+2]]+o[e[t+3]]+"-"+o[e[t+4]]+o[e[t+5]]+"-"+o[e[t+6]]+o[e[t+7]]+"-"+o[e[t+8]]+o[e[t+9]]+"-"+o[e[t+10]]+o[e[t+11]]+o[e[t+12]]+o[e[t+13]]+o[e[t+14]]+o[e[t+15]]).toLowerCase()}var l=function(e,t,s){if(r.randomUUID&&!t&&!e)return r.randomUUID();const o=(e=e||{}).random??e.rng?.()??function(){if(!a){if("undefined"===typeof crypto||!crypto.getRandomValues)throw new Error("crypto.getRandomValues() not supported. See https://github.com/uuidjs/uuid#getrandomvalues-not-supported");a=crypto.getRandomValues.bind(crypto)}return a(n)}();if(o.length<16)throw new Error("Random bytes length must be >= 16");if(o[6]=15&o[6]|64,o[8]=63&o[8]|128,t){if((s=s||0)<0||s+16>t.length)throw new RangeError(`UUID byte range ${s}:${s+15} is out of buffer bounds`);for(let e=0;e<16;++e)t[s+e]=o[e];return t}return c(o)};const i=(r,a,n,o,c="")=>{const i=(0,e.useRef)(new AbortController),u=(0,e.useRef)(null),d=(0,e.useRef)(o);return(0,e.useCallback)((async e=>{const o=`${r}${a}?${e.toString()}`;i.current.abort(),i.current=new AbortController;const p={Accept:"application/json",Authorization:n},g=(e=>{const t=l().replaceAll("-","");return(0,s.applyFilters)("ep.requestId",e+t)})(c);return g&&(p["X-ElasticPress-Request-ID"]=g),u.current=fetch(o,{signal:i.current.signal,headers:p}).then((e=>{if(!e.ok){if(401===e.status&&d.current)return d.current(),"";throw new Error((0,t.sprintf)((0,t.__)("HTTP %d.","elasticpress"),e.status))}return e.json()})).catch((e=>{if("AbortError"!==e?.name)throw e})).finally((()=>{u.current=null})),u.current}),[r,a,n,c])},u=(e,t,s="")=>{const r=new URLSearchParams;return Object.entries(t).forEach((([t,a])=>{const n=s+t,o="undefined"!==typeof e[t]?((e,t,s=!0)=>{let r=null;switch(e&&t.type){case"number":case"string":r=e;break;case"numbers":case"strings":r=e.join(",")}return t.allowedValues&&(r=t.allowedValues.includes(r)?r:null),s&&null===r&&"undefined"!==typeof t.default&&(r=t.default),r})(e[t],a):null;null!==o&&r.set(n,o)})),r},d=(e,t="")=>{const s=new URLSearchParams(window.location.search);return Object.entries(e).reduce(((e,[r,a])=>{const n=s.get(t+r),o="undefined"!==typeof n?((e,t,s=!0)=>{let r=null;switch(e&&t.type){case"number":r=parseFloat(e,10)||null;break;case"numbers":r=decodeURIComponent(e).split(",").map((e=>parseFloat(e,10))).filter(Boolean);break;case"string":r=e.toString();break;case"strings":r=decodeURIComponent(e).split(",").map((e=>e.toString().trim()))}return t.allowedValues&&(r=t.allowedValues.includes(r)?r:null),s&&null===r&&"undefined"!==typeof t.default&&(r=t.default),r})(n,a,!1):null;return null!==o&&(e[r]=o),e}),{})},p=(e,t)=>{const s=new URL(window.location.href),r=Array.from(s.searchParams.keys());for(const t of r)t.startsWith(e)&&s.searchParams.delete(t);return t&&t.forEach(((e,t)=>{s.searchParams.set(t,e)})),s.toString()},g=(e,t)=>{const s={...e};return Object.entries(t).forEach((([e,t])=>{Object.hasOwnProperty.call(t,"default")||delete s[e]})),s};var m=(e,t)=>{const s={...e,isPoppingState:!1};switch(t.type){case"CLEAR_CONSTRAINTS":{const e=g(s.args,s.argsSchema);s.args=e,s.args.offset=0;break}case"CLEAR_RESULTS":s.aggregations={},s.searchResults=[],s.totalResults=0;break;case"SEARCH":{const{updateDefaults:e,...r}=t.args;s.args={...s.args,...r,offset:0},s.isOn=!0,e&&r.post_type.length&&(s.argsSchema.post_type.default=r.post_type);break}case"SEARCH_FOR":{const e=g(s.args,s.argsSchema);s.args=e,s.args.search=t.searchTerm,s.args.offset=0,s.isOn=!0;break}case"SET_IS_LOADING":s.isLoading=t.isLoading;break;case"TURN_OFF":s.args={...s.args},s.isOn=!1;break;case"SET_RESULTS":{const{hits:{hits:e,total:r},aggregations:a,suggest:n}=t.response;s.isFirstSearch=!1;const o="number"===typeof r?r:r.value;s.aggregations=a,s.searchResults=e,s.searchTerm=s.args.search,s.totalResults=o,s.suggestedTerms=n?.ep_suggestion?.[0]?.options||[];break}case"NEXT_PAGE":s.args.offset+=s.args.per_page;break;case"PREVIOUS_PAGE":s.args.offset=Math.max(s.args.offset-s.args.per_page,0);break;case"POP_STATE":{const{isOn:e,args:r}=t.args;s.args=r,s.isOn=e,s.isPoppingState=!0;break}}return s};const f=(0,e.createContext)(),h=({apiEndpoint:s,apiHost:r,authorization:a,requestIdBase:n,argsSchema:o,children:c,paramPrefix:l,onAuthError:g})=>{const h=(0,e.useMemo)((()=>l?d(o,l):{}),[o,l]),b=(0,e.useMemo)((()=>{const e=(e=>Object.entries(e).reduce(((e,[t,s])=>(Object.hasOwnProperty.call(s,"default")&&(e[t]=s.default),e)),{}))(o);return{...e,...h}}),[o,h]),w=(0,e.useMemo)((()=>Object.keys(h).length>0),[h]),R=i(r,s,a,g,n),[y,E]=(0,e.useReducer)(m,{aggregations:{},args:b,argsSchema:o,isLoading:!1,isOn:w,isPoppingState:!1,searchResults:[],totalResults:0,suggestedTerms:[],isFirstSearch:!0,searchTerm:""}),_=(0,e.useRef)(y);_.current=y;const S=(0,e.useCallback)((()=>{E({type:"CLEAR_CONSTRAINTS"})}),[]),v=(0,e.useCallback)((()=>{E({type:"CLEAR_RESULTS"})}),[]),k=(0,e.useCallback)((e=>{E({type:"SEARCH",args:e})}),[]),O=e=>{E({type:"SET_IS_LOADING",isLoading:e})},C=e=>{E({type:"SET_RESULTS",response:e})},T=(0,e.useCallback)((()=>{if("undefined"===typeof l)return;const{args:e,isOn:t}=_.current,s={args:e,isOn:t};if(window.history.state)if(t){const t=u(e,o,l),r=p(l,t);window.history.pushState(s,document.title,r)}else{const e=p(l);window.history.pushState(s,document.title,e)}else window.history.replaceState(s,document.title,window.location.href)}),[o,l]),P=(0,e.useCallback)((e=>{if("undefined"===typeof l)return;e.state&&Object.keys(e.state).length>0&&(e=>{E({type:"POP_STATE",args:e})})(e.state)}),[l]),U=(0,e.useCallback)((()=>(window.addEventListener("popstate",P),()=>{window.removeEventListener("popstate",P)})),[P]),A=(0,e.useCallback)((()=>{(async()=>{const{args:e,isOn:s,isPoppingState:r}=_.current;if(r||T(),!s)return;const a=u(e,o);O(!0);try{const e=await R(a);if(!e)return;C(e)}catch(e){const s=(0,t.sprintf)((0,t.__)("ElasticPress: Unable to fetch results. %s","elasticpress"),e.message);console.error(s)}O(!1)})()}),[o,R,T]);(0,e.useEffect)(U,[U]),(0,e.useEffect)(A,[A,y.args,y.args.orderby,y.args.order,y.args.offset,y.args.search]);const{aggregations:F,args:L,isLoading:I,isOn:N,searchResults:j,searchTerm:$,totalResults:x,suggestedTerms:D,isFirstSearch:V}=_.current,B={aggregations:F,args:L,clearConstraints:S,clearResults:v,getUrlParamsFromArgs:u,getUrlWithParams:p,isLoading:I,isOn:N,searchResults:j,searchTerm:$,search:k,searchFor:e=>{E({type:"SEARCH_FOR",searchTerm:e})},setResults:C,nextPage:()=>{E({type:"NEXT_PAGE"})},previousPage:()=>{E({type:"PREVIOUS_PAGE"})},totalResults:x,turnOff:()=>{E({type:"TURN_OFF"})},suggestedTerms:D,isFirstSearch:V};return React.createElement(f.Provider,{value:B},c)};function b(){return b=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var s=arguments[t];for(var r in s)({}).hasOwnProperty.call(s,r)&&(e[r]=s[r])}return e},b.apply(null,arguments)}var w=({children:s,id:r,isBusy:a,onSelect:n,...o})=>{const[c,l]=(0,e.useState)(!1),[i,u]=(0,e.useState)(!1),d=(0,e.useMemo)((()=>s[i]?s[i].props.id:null),[s,i]),p=(0,e.useMemo)((()=>s.length&&!a?(0,t.sprintf)((0,t._n)("%d suggestion available, use the up and down keys to browse and the enter key to open","%d suggestions available, use the up and down keys to browse and the enter key to open",s.length,"elasticpress"),s.length):""),[s,a]),g=(0,e.useMemo)((()=>!(!1===i||!s[i])&&s[i].props.value),[s,i]),m=(0,e.useCallback)((()=>!!s.length&&0),[s]),f=(0,e.useCallback)((()=>!!s.length&&s.length-1),[s]),h=(0,e.useCallback)((()=>{const e=m(s);if(!1===i)return e;const t=i+1;return s?.[t]?t:e}),[s,m,i]),w=(0,e.useCallback)((()=>{const e=f(s);if(!1===i)return e;const t=i-1;return s?.[t]?t:e}),[s,f,i]),R=(0,e.useCallback)((()=>{l(!!s.length)}),[s]),y=(0,e.useCallback)((e=>{e.currentTarget.contains(e.relatedTarget)||(u(!1),l(!1))}),[]),E=(0,e.useCallback)((e=>{const t=h(i,s),r=w(i,s);switch(e.key){case"ArrowDown":e.preventDefault(),u(t);break;case"ArrowUp":e.preventDefault(),u(r);break;case"Enter":!1!==g&&(e.preventDefault(),n(g,e));break;case"Escape":c&&(e.preventDefault(),u(!1),l(!1))}}),[s,h,w,c,n,i,g]);return(0,e.useEffect)((()=>{u(!1),l(!!s.length)}),[s]),(0,e.useEffect)((()=>{!1!==i&&l(!0)}),[i]),React.createElement("div",{className:"ep-combobox",onBlur:y},React.createElement("input",b({"aria-activedescendant":d,"aria-autocomplete":"list","aria-controls":r,"aria-describedby":`${r}-description`,"aria-expanded":!a&&c,autoComplete:"off",className:"ep-combobox__input",onFocus:R,onKeyDown:E,role:"combobox"},o)),React.createElement("div",{id:`${r}-description`,className:"screen-reader-text"},p),React.createElement("ul",{id:r,role:"listbox",className:"ep-combobox__list"},s.map(((e,t)=>{const{id:s,value:r}=e.props;return React.createElement("li",{"aria-selected":i===t,className:"ep-combobox__option",key:s,onClick:e=>{n(r,e)},role:"option",tabIndex:"-1"},e)}))))},R=window.wp.date,y=({dateFormat:e,hit:s,statusLabels:r,timeFormat:a})=>{const{meta:{_billing_email:[{value:n}={}]=[],_billing_first_name:[{value:o}={}]=[],_billing_last_name:[{value:c}={}]=[],_items:[{value:l}={}]=[]},post_date_gmt:i,post_id:u,post_status:d}=s._source,p=`${i.split(" ").join("T")}+00:00`,g=(0,R.dateI18n)(e,p),m=(0,R.dateI18n)(a,p),f=l?l.split("|").length:0,h=`status-${d.substring(3)}`,b=r[d];return React.createElement("div",{className:"ep-suggestion"},React.createElement("div",{className:"ep-suggestion__header"},React.createElement("div",{className:"ep-suggestion__title"},`#${u}`," ",o," ",c),n),React.createElement("div",{className:"ep-suggestion__footer"},React.createElement("div",{className:"ep-suggestion__details"},(0,t.sprintf)((0,t._n)("%1$d item @ %2$s","%1$d items @ %2$s",f,"elasticpress"),f,m),React.createElement("br",null),g),b&&React.createElement("div",{className:`order-status ${h} tips`},React.createElement("span",null,b))))};function E(){return E=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var s=arguments[t];for(var r in s)({}).hasOwnProperty.call(s,r)&&(e[r]=s[r])}return e},E.apply(null,arguments)}var _=({adminUrl:t,dateFormat:s,statusLabels:r,timeFormat:a,value:n,...o})=>{const{clearResults:c,isLoading:l,searchFor:i,searchResults:u}=(0,e.useContext)(f),d=((t,s)=>{const r=(0,e.useRef)(null);return(0,e.useCallback)(((...e)=>{window.clearTimeout(r.current),r.current=window.setTimeout((()=>{t(...e)}),s)}),[t,s])})((e=>{i(e)}),300),p=(0,e.useCallback)((e=>{const{value:t}=e.target;t?d(e.target.value):c()}),[c,d]),g=(0,e.useCallback)(((e,s)=>{window.open(`${t}?post=${e}&action=edit`,s.metaKey?"_blank":"_self")}),[t]);return React.createElement(w,E({defaultValue:n,id:"ep-orders-suggestions",isBusy:l,onInput:p,onSelect:g},o),u.map((e=>{const{_id:t,_source:{post_id:n}}=e;return React.createElement(y,{dateFormat:s,id:`ep-order-suggestion-${t}`,hit:e,key:t,statusLabels:r,timeFormat:a,value:n})})))};const{adminUrl:S,apiEndpoint:v,apiHost:k,credentialsApiUrl:O,credentialsNonce:C,argsSchema:T,dateFormat:P,statusLabels:U,timeFormat:A,requestIdBase:F}=window.epWooCommerceOrderSearch;function L(){return L=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var s=arguments[t];for(var r in s)({}).hasOwnProperty.call(s,r)&&(e[r]=s[r])}return e},L.apply(null,arguments)}const I=({children:t})=>{const[s,r]=(0,e.useState)(null),a=(0,e.useRef)(!1);return(0,e.useEffect)((()=>{fetch(O,{headers:{"X-WP-Nonce":C}}).then((e=>e.text())).then(r)}),[]),s?React.createElement(h,{apiEndpoint:v,apiHost:k,argsSchema:T,authorization:`Basic ${s}`,requestIdBase:F,onAuthError:()=>{a.current?r(null):(fetch(O,{headers:{"X-WP-Nonce":C},method:"POST"}).then((e=>e.text())).then(r),a.current=!0)}},t):null};(async()=>{const t=document.querySelector("#posts-filter, #wc-orders-filter").s;if(!t)return;const s=Object.values(t.attributes).reduce(((e,t)=>({...e,[t.name]:t.value})),{}),r=document.createElement("div");if(r.setAttribute("id","ep-woocommerce-order-search"),t.replaceWith(r),"function"===typeof e.createRoot){(0,e.createRoot)(r).render(React.createElement(I,null,React.createElement(_,L({adminUrl:S,dateFormat:P,statusLabels:U,timeFormat:A},s))))}else(0,e.render)(React.createElement(I,null,React.createElement(_,L({adminUrl:S,dateFormat:P,statusLabels:U,timeFormat:A},s))),r)})()}();