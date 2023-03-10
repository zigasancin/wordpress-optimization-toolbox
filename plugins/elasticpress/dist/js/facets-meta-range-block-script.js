!function(){"use strict";var e={5251:function(e,t,n){var s=n(9196),i=60103;if(t.Fragment=60107,"function"===typeof Symbol&&Symbol.for){var r=Symbol.for;i=r("react.element"),t.Fragment=r("react.fragment")}var a=s.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,o=Object.prototype.hasOwnProperty,u={key:!0,ref:!0,__self:!0,__source:!0};function p(e,t,n){var s,r={},p=null,l=null;for(s in void 0!==n&&(p=""+n),void 0!==t.key&&(p=""+t.key),void 0!==t.ref&&(l=t.ref),t)o.call(t,s)&&!u.hasOwnProperty(s)&&(r[s]=t[s]);if(e&&e.defaultProps)for(s in t=e.defaultProps)void 0===r[s]&&(r[s]=t[s]);return{$$typeof:i,type:e,key:p,ref:l,props:r,_owner:a.current}}t.jsx=p,t.jsxs=p},5893:function(e,t,n){e.exports=n(5251)},9196:function(e){e.exports=window.React}},t={};function n(s){var i=t[s];if(void 0!==i)return i.exports;var r=t[s]={exports:{}};return e[s](r,r.exports,n),r.exports}n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,{a:t}),t},n.d=function(e,t){for(var s in t)n.o(t,s)&&!n.o(e,s)&&Object.defineProperty(e,s,{enumerable:!0,get:t[s]})},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){var e=window.wp.blocks,t=window.wp.apiFetch,s=n.n(t),i=window.wp.blockEditor,r=window.wp.components,a=window.wp.element,o=window.wp.i18n,u=n(9196),p=n.n(u),l="/Users/brians/git/react-slider/src/components/ReactSlider/ReactSlider.jsx";function c(){return c=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var s in n)Object.prototype.hasOwnProperty.call(n,s)&&(e[s]=n[s])}return e},c.apply(this,arguments)}function h(e,t){return h=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e},h(e,t)}function f(e){return e&&e.stopPropagation&&e.stopPropagation(),e&&e.preventDefault&&e.preventDefault(),!1}function d(e){return null==e?[]:Array.isArray(e)?e.slice():[e]}function v(e){return null!==e&&1===e.length?e[0]:e.slice()}function m(e){Object.keys(e).forEach((function(t){"undefined"!==typeof document&&document.addEventListener(t,e[t],!1)}))}function g(e,t){return y(function(e,t){var n=e;n<=t.min&&(n=t.min);n>=t.max&&(n=t.max);return n}(e,t),t)}function y(e,t){var n=(e-t.min)%t.step,s=e-n;return 2*Math.abs(n)>=t.step&&(s+=n>0?t.step:-t.step),parseFloat(s.toFixed(5))}var b=function(e){var t,n;function s(t){var n;(n=e.call(this,t)||this).onKeyUp=function(){n.onEnd()},n.onMouseUp=function(){n.onEnd(n.getMouseEventMap())},n.onTouchEnd=function(){n.onEnd(n.getTouchEventMap())},n.onBlur=function(){n.setState({index:-1},n.onEnd(n.getKeyDownEventMap()))},n.onMouseMove=function(e){n.setState({pending:!0});var t=n.getMousePosition(e),s=n.getDiffPosition(t[0]),i=n.getValueFromPosition(s);n.move(i)},n.onTouchMove=function(e){if(!(e.touches.length>1)){n.setState({pending:!0});var t=n.getTouchPosition(e);if("undefined"===typeof n.isScrolling){var s=t[0]-n.startPosition[0],i=t[1]-n.startPosition[1];n.isScrolling=Math.abs(i)>Math.abs(s)}if(n.isScrolling)n.setState({index:-1});else{var r=n.getDiffPosition(t[0]),a=n.getValueFromPosition(r);n.move(a)}}},n.onKeyDown=function(e){if(!(e.ctrlKey||e.shiftKey||e.altKey||e.metaKey))switch(n.setState({pending:!0}),e.key){case"ArrowLeft":case"ArrowDown":case"Left":case"Down":e.preventDefault(),n.moveDownByStep();break;case"ArrowRight":case"ArrowUp":case"Right":case"Up":e.preventDefault(),n.moveUpByStep();break;case"Home":e.preventDefault(),n.move(n.props.min);break;case"End":e.preventDefault(),n.move(n.props.max);break;case"PageDown":e.preventDefault(),n.moveDownByStep(n.props.pageFn(n.props.step));break;case"PageUp":e.preventDefault(),n.moveUpByStep(n.props.pageFn(n.props.step))}},n.onSliderMouseDown=function(e){if(!n.props.disabled&&2!==e.button){if(n.setState({pending:!0}),!n.props.snapDragDisabled){var t=n.getMousePosition(e);n.forceValueFromPosition(t[0],(function(e){n.start(e,t[0]),m(n.getMouseEventMap())}))}f(e)}},n.onSliderClick=function(e){if(!n.props.disabled&&n.props.onSliderClick&&!n.hasMoved){var t=n.getMousePosition(e),s=g(n.calcValue(n.calcOffsetFromPosition(t[0])),n.props);n.props.onSliderClick(s)}},n.createOnKeyDown=function(e){return function(t){n.props.disabled||(n.start(e),m(n.getKeyDownEventMap()),f(t))}},n.createOnMouseDown=function(e){return function(t){if(!n.props.disabled&&2!==t.button){n.setState({pending:!0});var s=n.getMousePosition(t);n.start(e,s[0]),m(n.getMouseEventMap()),f(t)}}},n.createOnTouchStart=function(e){return function(t){if(!(n.props.disabled||t.touches.length>1)){n.setState({pending:!0});var s=n.getTouchPosition(t);n.startPosition=s,n.isScrolling=void 0,n.start(e,s[0]),m(n.getTouchEventMap()),function(e){e.stopPropagation&&e.stopPropagation()}(t)}}},n.handleResize=function(){var e=window.setTimeout((function(){n.pendingResizeTimeouts.shift(),n.resize()}),0);n.pendingResizeTimeouts.push(e)},n.renderThumb=function(e,t){var s=n.props.thumbClassName+" "+n.props.thumbClassName+"-"+t+" "+(n.state.index===t?n.props.thumbActiveClassName:""),i={ref:function(e){n["thumb"+t]=e},key:n.props.thumbClassName+"-"+t,className:s,style:e,onMouseDown:n.createOnMouseDown(t),onTouchStart:n.createOnTouchStart(t),onFocus:n.createOnKeyDown(t),tabIndex:0,role:"slider","aria-orientation":n.props.orientation,"aria-valuenow":n.state.value[t],"aria-valuemin":n.props.min,"aria-valuemax":n.props.max,"aria-label":Array.isArray(n.props.ariaLabel)?n.props.ariaLabel[t]:n.props.ariaLabel,"aria-labelledby":Array.isArray(n.props.ariaLabelledby)?n.props.ariaLabelledby[t]:n.props.ariaLabelledby},r={index:t,value:v(n.state.value),valueNow:n.state.value[t]};return n.props.ariaValuetext&&(i["aria-valuetext"]="string"===typeof n.props.ariaValuetext?n.props.ariaValuetext:n.props.ariaValuetext(r)),n.props.renderThumb(i,r)},n.renderTrack=function(e,t,s){var i={key:n.props.trackClassName+"-"+e,className:n.props.trackClassName+" "+n.props.trackClassName+"-"+e,style:n.buildTrackStyle(t,n.state.upperBound-s)},r={index:e,value:v(n.state.value)};return n.props.renderTrack(i,r)};var s=d(t.value);s.length||(s=d(t.defaultValue)),n.pendingResizeTimeouts=[];for(var i=[],r=0;r<s.length;r+=1)s[r]=g(s[r],t),i.push(r);return n.state={index:-1,upperBound:0,sliderLength:0,value:s,zIndices:i},n}n=e,(t=s).prototype=Object.create(n.prototype),t.prototype.constructor=t,h(t,n);var i=s.prototype;return i.componentDidMount=function(){"undefined"!==typeof window&&(window.addEventListener("resize",this.handleResize),this.resize())},s.getDerivedStateFromProps=function(e,t){var n=d(e.value);return n.length?t.pending?null:{value:n.map((function(t){return g(t,e)}))}:null},i.componentDidUpdate=function(){0===this.state.upperBound&&this.resize()},i.componentWillUnmount=function(){this.clearPendingResizeTimeouts(),"undefined"!==typeof window&&window.removeEventListener("resize",this.handleResize)},i.onEnd=function(e){e&&function(e){Object.keys(e).forEach((function(t){"undefined"!==typeof document&&document.removeEventListener(t,e[t],!1)}))}(e),this.hasMoved&&this.fireChangeEvent("onAfterChange"),this.setState({pending:!1}),this.hasMoved=!1},i.getValue=function(){return v(this.state.value)},i.getClosestIndex=function(e){for(var t=Number.MAX_VALUE,n=-1,s=this.state.value,i=s.length,r=0;r<i;r+=1){var a=this.calcOffset(s[r]),o=Math.abs(e-a);o<t&&(t=o,n=r)}return n},i.getMousePosition=function(e){return[e["page"+this.axisKey()],e["page"+this.orthogonalAxisKey()]]},i.getTouchPosition=function(e){var t=e.touches[0];return[t["page"+this.axisKey()],t["page"+this.orthogonalAxisKey()]]},i.getKeyDownEventMap=function(){return{keydown:this.onKeyDown,keyup:this.onKeyUp,focusout:this.onBlur}},i.getMouseEventMap=function(){return{mousemove:this.onMouseMove,mouseup:this.onMouseUp}},i.getTouchEventMap=function(){return{touchmove:this.onTouchMove,touchend:this.onTouchEnd}},i.getValueFromPosition=function(e){var t=e/(this.state.sliderLength-this.state.thumbSize)*(this.props.max-this.props.min);return g(this.state.startValue+t,this.props)},i.getDiffPosition=function(e){var t=e-this.state.startPosition;return this.props.invert&&(t*=-1),t},i.resize=function(){var e=this.slider,t=this.thumb0;if(e&&t){var n=this.sizeKey(),s=e.getBoundingClientRect(),i=e[n],r=s[this.posMaxKey()],a=s[this.posMinKey()],o=t.getBoundingClientRect()[n.replace("client","").toLowerCase()],u=i-o,p=Math.abs(r-a);this.state.upperBound===u&&this.state.sliderLength===p&&this.state.thumbSize===o||this.setState({upperBound:u,sliderLength:p,thumbSize:o})}},i.calcOffset=function(e){var t=this.props.max-this.props.min;return 0===t?0:(e-this.props.min)/t*this.state.upperBound},i.calcValue=function(e){return e/this.state.upperBound*(this.props.max-this.props.min)+this.props.min},i.calcOffsetFromPosition=function(e){var t=this.slider.getBoundingClientRect(),n=t[this.posMaxKey()],s=t[this.posMinKey()],i=e-(window["page"+this.axisKey()+"Offset"]+(this.props.invert?n:s));return this.props.invert&&(i=this.state.sliderLength-i),i-=this.state.thumbSize/2},i.forceValueFromPosition=function(e,t){var n=this,s=this.calcOffsetFromPosition(e),i=this.getClosestIndex(s),r=g(this.calcValue(s),this.props),a=this.state.value.slice();a[i]=r;for(var o=0;o<a.length-1;o+=1)if(a[o+1]-a[o]<this.props.minDistance)return;this.fireChangeEvent("onBeforeChange"),this.hasMoved=!0,this.setState({value:a},(function(){t(i),n.fireChangeEvent("onChange")}))},i.clearPendingResizeTimeouts=function(){do{var e=this.pendingResizeTimeouts.shift();clearTimeout(e)}while(this.pendingResizeTimeouts.length)},i.start=function(e,t){var n=this["thumb"+e];n&&n.focus();var s=this.state.zIndices;s.splice(s.indexOf(e),1),s.push(e),this.setState((function(n){return{startValue:n.value[e],startPosition:void 0!==t?t:n.startPosition,index:e,zIndices:s}}))},i.moveUpByStep=function(e){void 0===e&&(e=this.props.step);var t=g(this.state.value[this.state.index]+e,this.props);this.move(Math.min(t,this.props.max))},i.moveDownByStep=function(e){void 0===e&&(e=this.props.step);var t=g(this.state.value[this.state.index]-e,this.props);this.move(Math.max(t,this.props.min))},i.move=function(e){var t=this.state,n=t.index,s=t.value,i=s.length,r=s[n];if(e!==r){this.hasMoved||this.fireChangeEvent("onBeforeChange"),this.hasMoved=!0;var a=this.props,o=a.pearling,u=a.max,p=a.min,l=a.minDistance;if(!o){if(n>0){var c=s[n-1];e<c+l&&(e=c+l)}if(n<i-1){var h=s[n+1];e>h-l&&(e=h-l)}}s[n]=e,o&&i>1&&(e>r?(this.pushSucceeding(s,l,n),function(e,t,n,s){for(var i=0;i<e;i+=1){var r=s-i*n;t[e-1-i]>r&&(t[e-1-i]=r)}}(i,s,l,u)):e<r&&(this.pushPreceding(s,l,n),function(e,t,n,s){for(var i=0;i<e;i+=1){var r=s+i*n;t[i]<r&&(t[i]=r)}}(i,s,l,p))),this.setState({value:s},this.fireChangeEvent.bind(this,"onChange"))}},i.pushSucceeding=function(e,t,n){var s,i;for(i=e[s=n]+t;null!==e[s+1]&&i>e[s+1];i=e[s+=1]+t)e[s+1]=y(i,this.props)},i.pushPreceding=function(e,t,n){for(var s=n,i=e[s]-t;null!==e[s-1]&&i<e[s-1];i=e[s-=1]-t)e[s-1]=y(i,this.props)},i.axisKey=function(){return"vertical"===this.props.orientation?"Y":"X"},i.orthogonalAxisKey=function(){return"vertical"===this.props.orientation?"X":"Y"},i.posMinKey=function(){return"vertical"===this.props.orientation?this.props.invert?"bottom":"top":this.props.invert?"right":"left"},i.posMaxKey=function(){return"vertical"===this.props.orientation?this.props.invert?"top":"bottom":this.props.invert?"left":"right"},i.sizeKey=function(){return"vertical"===this.props.orientation?"clientHeight":"clientWidth"},i.fireChangeEvent=function(e){this.props[e]&&this.props[e](v(this.state.value),this.state.index)},i.buildThumbStyle=function(e,t){var n={position:"absolute",touchAction:"none",willChange:this.state.index>=0?this.posMinKey():"",zIndex:this.state.zIndices.indexOf(t)+1};return n[this.posMinKey()]=e+"px",n},i.buildTrackStyle=function(e,t){var n={position:"absolute",willChange:this.state.index>=0?this.posMinKey()+","+this.posMaxKey():""};return n[this.posMinKey()]=e,n[this.posMaxKey()]=t,n},i.buildMarkStyle=function(e){var t;return(t={position:"absolute"})[this.posMinKey()]=e,t},i.renderThumbs=function(e){for(var t=e.length,n=[],s=0;s<t;s+=1)n[s]=this.buildThumbStyle(e[s],s);for(var i=[],r=0;r<t;r+=1)i[r]=this.renderThumb(n[r],r);return i},i.renderTracks=function(e){var t=[],n=e.length-1;t.push(this.renderTrack(0,0,e[0]));for(var s=0;s<n;s+=1)t.push(this.renderTrack(s+1,e[s],e[s+1]));return t.push(this.renderTrack(n+1,e[n],this.state.upperBound)),t},i.renderMarks=function(){var e=this,t=this.props.marks,n=this.props.max-this.props.min+1;return"boolean"===typeof t?t=Array.from({length:n}).map((function(e,t){return t})):"number"===typeof t&&(t=Array.from({length:n}).map((function(e,t){return t})).filter((function(e){return e%t===0}))),t.map(parseFloat).sort((function(e,t){return e-t})).map((function(t){var n=e.calcOffset(t),s={key:t,className:e.props.markClassName,style:e.buildMarkStyle(n)};return e.props.renderMark(s)}))},i.render=function(){for(var e=this,t=[],n=this.state.value,s=n.length,i=0;i<s;i+=1)t[i]=this.calcOffset(n[i],i);var r=this.props.withTracks?this.renderTracks(t):null,a=this.renderThumbs(t),o=this.props.marks?this.renderMarks():null;return p().createElement("div",{ref:function(t){e.slider=t},style:{position:"relative"},className:this.props.className+(this.props.disabled?" disabled":""),onMouseDown:this.onSliderMouseDown,onClick:this.onSliderClick},r,a,o)},s}(p().Component);b.displayName="ReactSlider",b.defaultProps={min:0,max:100,step:1,pageFn:function(e){return 10*e},minDistance:0,defaultValue:0,orientation:"horizontal",className:"slider",thumbClassName:"thumb",thumbActiveClassName:"active",trackClassName:"track",markClassName:"mark",withTracks:!0,pearling:!1,disabled:!1,snapDragDisabled:!1,invert:!1,marks:[],renderThumb:function(e){return p().createElement("div",c({},e,{__self:b,__source:{fileName:l,lineNumber:353,columnNumber:31}}))},renderTrack:function(e){return p().createElement("div",c({},e,{__self:b,__source:{fileName:l,lineNumber:354,columnNumber:31}}))},renderMark:function(e){return p().createElement("span",c({},e,{__self:b,__source:{fileName:l,lineNumber:355,columnNumber:30}}))}},b.propTypes={};var x=b,w=n(5893),M=({clearUrl:e,min:t,max:n,prefix:s,suffix:i,value:r,...a})=>(0,w.jsxs)("div",{className:"ep-range-facet",children:[(0,w.jsx)("div",{className:"ep-range-facet__slider",children:(0,w.jsx)(x,{className:"ep-range-slider",minDistance:1,thumbActiveClassName:"ep-range-slider__thumb--active",thumbClassName:"ep-range-slider__thumb",trackClassName:"ep-range-slider__track",min:t,max:n,value:r,...a})}),(0,w.jsxs)("div",{className:"ep-range-facet__values",children:[s,r[0],i," — ",s,r[1],i]}),(0,w.jsxs)("div",{className:"ep-range-facet__action",children:[e?(0,w.jsx)("a",{href:e,children:(0,o.__)("Clear","elasticpress")}):null," ",(0,w.jsx)("button",{type:"submit",children:(0,o.__)("Filter","elasticpress")})]})]});const k=()=>(0,w.jsx)(r.Placeholder,{children:(0,w.jsx)(r.Spinner,{})}),S=({min:e,max:t,prefix:n,suffix:s})=>(0,w.jsx)(r.Disabled,{children:(0,w.jsx)(M,{min:e,max:t,prefix:n,suffix:s,value:[e,t]})}),_=({value:e})=>(0,w.jsx)(i.Warning,{children:(0,o.sprintf)((0,o.__)('Preview unavailable. The "%s" field does not appear to contain numeric values. Select a new meta field key or populate the field with numeric values to enable filtering by range.',"elasticpress"),e)}),C=({onChange:e,options:t,value:n})=>(0,w.jsx)(r.SelectControl,{disabled:t.length<=1,help:(0,a.createInterpolateElement)((0,o.__)("This is the list of meta fields indexed in Elasticsearch. If your desired field does not appear in this list please try to <a>sync your content</a>","elasticpress"),{a:(0,w.jsx)("a",{href:facetMetaBlock.syncUrl})}),label:(0,o.__)("Meta Field Key","elasticpress"),onChange:e,options:t,value:n}),P=e=>(0,w.jsx)(r.Placeholder,{label:(0,o.__)("Facet by Meta Range","elasticpress"),children:(0,w.jsx)(C,{...e})});var T=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":2,"title":"Facet by Meta Range - Beta (ElasticPress)","textdomain":"elasticpress","name":"elasticpress/facet-meta-range","icon":"feedback","category":"widgets","attributes":{"facet":{"type":"string","default":""},"prefix":{"type":"string","default":""},"suffix":{"type":"string","default":""}},"supports":{"html":false},"editorScript":"ep-facets-meta-range-block-script","style":"elasticpress-facets","viewScript":"ep-facets-meta-range-block-view-script"}');(0,e.registerBlockType)(T,{edit:e=>{const{attributes:t,setAttributes:n}=e,{facet:u,prefix:p,suffix:l}=t,c=(0,i.useBlockProps)(),[h,f]=(0,a.useState)(!1),[d,v]=(0,a.useState)(!1),[m,g]=(0,a.useState)(!1),[y,b]=(0,a.useState)([]),x=(0,a.useMemo)((()=>[{label:(0,o.__)("Select key","elasticpress"),value:""},...y.map((e=>({label:e,value:e})))]),[y]),M=e=>{n({facet:e})};return(0,a.useEffect)((()=>{f(!0);const e=new URLSearchParams({facet:u});s()({path:`/elasticpress/v1/facets/meta-range/block-preview?${e}`}).then((e=>{e.success?(v(e.data.min),g(e.data.max)):(v(!1),g(!1))})).finally((()=>f(!1)))}),[u]),(0,a.useEffect)((()=>{s()({path:"/elasticpress/v1/facets/meta-range/keys"}).then(b)}),[]),(0,w.jsxs)(w.Fragment,{children:[(0,w.jsx)(i.InspectorControls,{children:(0,w.jsxs)(r.PanelBody,{title:(0,o.__)("Facet Settings","elasticpress"),children:[(0,w.jsx)(C,{onChange:M,options:x,value:u}),(0,w.jsx)(r.TextControl,{label:(0,o.__)("Value prefix","elasticpress"),onChange:e=>{n({prefix:e})},value:p}),(0,w.jsx)(r.TextControl,{label:(0,o.__)("Value suffix","elasticpress"),onChange:e=>{n({suffix:e})},value:l})]})}),(0,w.jsx)("div",{...c,children:u?h?(0,w.jsx)(k,{}):!1!==d&&!1!==m?(0,w.jsx)(S,{min:d,max:m,prefix:p,suffix:l}):(0,w.jsx)(_,{value:u}):(0,w.jsx)(P,{onChange:M,options:x,value:u})})]})},save:()=>{}})}()}();