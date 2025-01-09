!function(){"use strict";var e={n:function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,{a:n}),n},d:function(t,n){for(var s in n)e.o(n,s)&&!e.o(t,s)&&Object.defineProperty(t,s,{enumerable:!0,get:n[s]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)}},t=window.wp.element,n=window.wp.domReady,s=e.n(n);function i(e,t){return i=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(e,t){return e.__proto__=t,e},i(e,t)}var o=window.React;function r(e){return e&&e.stopPropagation&&e.stopPropagation(),e&&e.preventDefault&&e.preventDefault(),!1}function a(e){return null==e?[]:Array.isArray(e)?e.slice():[e]}function p(e){return null!==e&&1===e.length?e[0]:e.slice()}function u(e){Object.keys(e).forEach((t=>{"undefined"!=typeof document&&document.addEventListener(t,e[t],!1)}))}function l(e,t){return c(function(e,t){let n=e;return n<=t.min&&(n=t.min),n>=t.max&&(n=t.max),n}(e,t),t)}function c(e,t){const n=(e-t.min)%t.step;let s=e-n;return 2*Math.abs(n)>=t.step&&(s+=n>0?t.step:-t.step),parseFloat(s.toFixed(5))}let h=function(e){function t(t){var n;(n=e.call(this,t)||this).onKeyUp=()=>{n.onEnd()},n.onMouseUp=()=>{n.onEnd(n.getMouseEventMap())},n.onTouchEnd=e=>{e.preventDefault(),n.onEnd(n.getTouchEventMap())},n.onBlur=()=>{n.setState({index:-1},n.onEnd(n.getKeyDownEventMap()))},n.onMouseMove=e=>{n.setState({pending:!0});const t=n.getMousePosition(e),s=n.getDiffPosition(t[0]),i=n.getValueFromPosition(s);n.move(i)},n.onTouchMove=e=>{if(e.touches.length>1)return;n.setState({pending:!0});const t=n.getTouchPosition(e);if(void 0===n.isScrolling){const e=t[0]-n.startPosition[0],s=t[1]-n.startPosition[1];n.isScrolling=Math.abs(s)>Math.abs(e)}if(n.isScrolling)return void n.setState({index:-1});const s=n.getDiffPosition(t[0]),i=n.getValueFromPosition(s);n.move(i)},n.onKeyDown=e=>{if(!(e.ctrlKey||e.shiftKey||e.altKey||e.metaKey))switch(n.setState({pending:!0}),e.key){case"ArrowLeft":case"ArrowDown":case"Left":case"Down":e.preventDefault(),n.moveDownByStep();break;case"ArrowRight":case"ArrowUp":case"Right":case"Up":e.preventDefault(),n.moveUpByStep();break;case"Home":e.preventDefault(),n.move(n.props.min);break;case"End":e.preventDefault(),n.move(n.props.max);break;case"PageDown":e.preventDefault(),n.moveDownByStep(n.props.pageFn(n.props.step));break;case"PageUp":e.preventDefault(),n.moveUpByStep(n.props.pageFn(n.props.step))}},n.onSliderMouseDown=e=>{if(!n.props.disabled&&2!==e.button){if(n.setState({pending:!0}),!n.props.snapDragDisabled){const t=n.getMousePosition(e);n.forceValueFromPosition(t[0],(e=>{n.start(e,t[0]),u(n.getMouseEventMap())}))}r(e)}},n.onSliderClick=e=>{if(!n.props.disabled&&n.props.onSliderClick&&!n.hasMoved){const t=n.getMousePosition(e),s=l(n.calcValue(n.calcOffsetFromPosition(t[0])),n.props);n.props.onSliderClick(s)}},n.createOnKeyDown=e=>t=>{n.props.disabled||(n.start(e),u(n.getKeyDownEventMap()),r(t))},n.createOnMouseDown=e=>t=>{if(n.props.disabled||2===t.button)return;n.setState({pending:!0});const s=n.getMousePosition(t);n.start(e,s[0]),u(n.getMouseEventMap()),r(t)},n.createOnTouchStart=e=>t=>{if(n.props.disabled||t.touches.length>1)return;n.setState({pending:!0});const s=n.getTouchPosition(t);n.startPosition=s,n.isScrolling=void 0,n.start(e,s[0]),u(n.getTouchEventMap()),function(e){e.stopPropagation&&e.stopPropagation()}(t)},n.handleResize=()=>{const e=window.setTimeout((()=>{n.pendingResizeTimeouts.shift(),n.resize()}),0);n.pendingResizeTimeouts.push(e)},n.renderThumb=(e,t)=>{const s=n.props.thumbClassName+" "+n.props.thumbClassName+"-"+t+" "+(n.state.index===t?n.props.thumbActiveClassName:""),i={ref:e=>{n["thumb"+t]=e},key:n.props.thumbClassName+"-"+t,className:s,style:e,onMouseDown:n.createOnMouseDown(t),onTouchStart:n.createOnTouchStart(t),onFocus:n.createOnKeyDown(t),tabIndex:0,role:"slider","aria-orientation":n.props.orientation,"aria-valuenow":n.state.value[t],"aria-valuemin":n.props.min,"aria-valuemax":n.props.max,"aria-label":Array.isArray(n.props.ariaLabel)?n.props.ariaLabel[t]:n.props.ariaLabel,"aria-labelledby":Array.isArray(n.props.ariaLabelledby)?n.props.ariaLabelledby[t]:n.props.ariaLabelledby,"aria-disabled":n.props.disabled},o={index:t,value:p(n.state.value),valueNow:n.state.value[t]};return n.props.ariaValuetext&&(i["aria-valuetext"]="string"==typeof n.props.ariaValuetext?n.props.ariaValuetext:n.props.ariaValuetext(o)),n.props.renderThumb(i,o)},n.renderTrack=(e,t,s)=>{const i={key:n.props.trackClassName+"-"+e,className:n.props.trackClassName+" "+n.props.trackClassName+"-"+e,style:n.buildTrackStyle(t,n.state.upperBound-s)},o={index:e,value:p(n.state.value)};return n.props.renderTrack(i,o)};let s=a(t.value);s.length||(s=a(t.defaultValue)),n.pendingResizeTimeouts=[];const i=[];for(let e=0;e<s.length;e+=1)s[e]=l(s[e],t),i.push(e);return n.resizeObserver=null,n.resizeElementRef=o.createRef(),n.state={index:-1,upperBound:0,sliderLength:0,value:s,zIndices:i},n}!function(e,t){e.prototype=Object.create(t.prototype),e.prototype.constructor=e,i(e,t)}(t,e);var n=t.prototype;return n.componentDidMount=function(){"undefined"!=typeof window&&(this.resizeObserver=new ResizeObserver(this.handleResize),this.resizeObserver.observe(this.resizeElementRef.current),this.resize())},t.getDerivedStateFromProps=function(e,t){const n=a(e.value);return n.length?t.pending?null:{value:n.map((t=>l(t,e)))}:null},n.componentDidUpdate=function(){0===this.state.upperBound&&this.resize()},n.componentWillUnmount=function(){this.clearPendingResizeTimeouts(),this.resizeObserver&&this.resizeObserver.disconnect()},n.onEnd=function(e){e&&function(e){Object.keys(e).forEach((t=>{"undefined"!=typeof document&&document.removeEventListener(t,e[t],!1)}))}(e),this.hasMoved&&this.fireChangeEvent("onAfterChange"),this.setState({pending:!1}),this.hasMoved=!1},n.getValue=function(){return p(this.state.value)},n.getClosestIndex=function(e){let t=Number.MAX_VALUE,n=-1;const{value:s}=this.state,i=s.length;for(let o=0;o<i;o+=1){const i=this.calcOffset(s[o]),r=Math.abs(e-i);r<t&&(t=r,n=o)}return n},n.getMousePosition=function(e){return[e["page"+this.axisKey()],e["page"+this.orthogonalAxisKey()]]},n.getTouchPosition=function(e){const t=e.touches[0];return[t["page"+this.axisKey()],t["page"+this.orthogonalAxisKey()]]},n.getKeyDownEventMap=function(){return{keydown:this.onKeyDown,keyup:this.onKeyUp,focusout:this.onBlur}},n.getMouseEventMap=function(){return{mousemove:this.onMouseMove,mouseup:this.onMouseUp}},n.getTouchEventMap=function(){return{touchmove:this.onTouchMove,touchend:this.onTouchEnd}},n.getValueFromPosition=function(e){const t=e/(this.state.sliderLength-this.state.thumbSize)*(this.props.max-this.props.min);return l(this.state.startValue+t,this.props)},n.getDiffPosition=function(e){let t=e-this.state.startPosition;return this.props.invert&&(t*=-1),t},n.resize=function(){const{slider:e,thumb0:t}=this;if(!e||!t)return;const n=this.sizeKey(),s=e.getBoundingClientRect(),i=e[n],o=s[this.posMaxKey()],r=s[this.posMinKey()],a=t.getBoundingClientRect()[n.replace("client","").toLowerCase()],p=i-a,u=Math.abs(o-r);this.state.upperBound===p&&this.state.sliderLength===u&&this.state.thumbSize===a||this.setState({upperBound:p,sliderLength:u,thumbSize:a})},n.calcOffset=function(e){const t=this.props.max-this.props.min;return 0===t?0:(e-this.props.min)/t*this.state.upperBound},n.calcValue=function(e){return e/this.state.upperBound*(this.props.max-this.props.min)+this.props.min},n.calcOffsetFromPosition=function(e){const{slider:t}=this,n=t.getBoundingClientRect(),s=n[this.posMaxKey()],i=n[this.posMinKey()];let o=e-(window["page"+this.axisKey()+"Offset"]+(this.props.invert?s:i));return this.props.invert&&(o=this.state.sliderLength-o),o-=this.state.thumbSize/2,o},n.forceValueFromPosition=function(e,t){const n=this.calcOffsetFromPosition(e),s=this.getClosestIndex(n),i=l(this.calcValue(n),this.props),o=this.state.value.slice();o[s]=i;for(let e=0;e<o.length-1;e+=1)if(o[e+1]-o[e]<this.props.minDistance)return;this.fireChangeEvent("onBeforeChange"),this.hasMoved=!0,this.setState({value:o},(()=>{t(s),this.fireChangeEvent("onChange")}))},n.clearPendingResizeTimeouts=function(){do{const e=this.pendingResizeTimeouts.shift();clearTimeout(e)}while(this.pendingResizeTimeouts.length)},n.start=function(e,t){const n=this["thumb"+e];n&&n.focus();const{zIndices:s}=this.state;s.splice(s.indexOf(e),1),s.push(e),this.setState((n=>({startValue:n.value[e],startPosition:void 0!==t?t:n.startPosition,index:e,zIndices:s})))},n.moveUpByStep=function(e){void 0===e&&(e=this.props.step);const t=this.state.value[this.state.index],n=l(this.props.invert&&"horizontal"===this.props.orientation?t-e:t+e,this.props);this.move(Math.min(n,this.props.max))},n.moveDownByStep=function(e){void 0===e&&(e=this.props.step);const t=this.state.value[this.state.index],n=l(this.props.invert&&"horizontal"===this.props.orientation?t+e:t-e,this.props);this.move(Math.max(n,this.props.min))},n.move=function(e){const t=this.state.value.slice(),{index:n}=this.state,{length:s}=t,i=t[n];if(e===i)return;this.hasMoved||this.fireChangeEvent("onBeforeChange"),this.hasMoved=!0;const{pearling:o,max:r,min:a,minDistance:p}=this.props;if(!o){if(n>0){const s=t[n-1];e<s+p&&(e=s+p)}if(n<s-1){const s=t[n+1];e>s-p&&(e=s-p)}}t[n]=e,o&&s>1&&(e>i?(this.pushSucceeding(t,p,n),function(e,t,n,s){for(let i=0;i<e;i+=1){const o=s-i*n;t[e-1-i]>o&&(t[e-1-i]=o)}}(s,t,p,r)):e<i&&(this.pushPreceding(t,p,n),function(e,t,n,s){for(let i=0;i<e;i+=1){const e=s+i*n;t[i]<e&&(t[i]=e)}}(s,t,p,a))),this.setState({value:t},this.fireChangeEvent.bind(this,"onChange"))},n.pushSucceeding=function(e,t,n){let s,i;for(s=n,i=e[s]+t;null!==e[s+1]&&i>e[s+1];s+=1,i=e[s]+t)e[s+1]=c(i,this.props)},n.pushPreceding=function(e,t,n){for(let s=n,i=e[s]-t;null!==e[s-1]&&i<e[s-1];s-=1,i=e[s]-t)e[s-1]=c(i,this.props)},n.axisKey=function(){return"vertical"===this.props.orientation?"Y":"X"},n.orthogonalAxisKey=function(){return"vertical"===this.props.orientation?"X":"Y"},n.posMinKey=function(){return"vertical"===this.props.orientation?this.props.invert?"bottom":"top":this.props.invert?"right":"left"},n.posMaxKey=function(){return"vertical"===this.props.orientation?this.props.invert?"top":"bottom":this.props.invert?"left":"right"},n.sizeKey=function(){return"vertical"===this.props.orientation?"clientHeight":"clientWidth"},n.fireChangeEvent=function(e){this.props[e]&&this.props[e](p(this.state.value),this.state.index)},n.buildThumbStyle=function(e,t){const n={position:"absolute",touchAction:"none",willChange:this.state.index>=0?this.posMinKey():void 0,zIndex:this.state.zIndices.indexOf(t)+1};return n[this.posMinKey()]=e+"px",n},n.buildTrackStyle=function(e,t){const n={position:"absolute",willChange:this.state.index>=0?this.posMinKey()+","+this.posMaxKey():void 0};return n[this.posMinKey()]=e,n[this.posMaxKey()]=t,n},n.buildMarkStyle=function(e){var t;return(t={position:"absolute"})[this.posMinKey()]=e,t},n.renderThumbs=function(e){const{length:t}=e,n=[];for(let s=0;s<t;s+=1)n[s]=this.buildThumbStyle(e[s],s);const s=[];for(let e=0;e<t;e+=1)s[e]=this.renderThumb(n[e],e);return s},n.renderTracks=function(e){const t=[],n=e.length-1;t.push(this.renderTrack(0,0,e[0]));for(let s=0;s<n;s+=1)t.push(this.renderTrack(s+1,e[s],e[s+1]));return t.push(this.renderTrack(n+1,e[n],this.state.upperBound)),t},n.renderMarks=function(){let{marks:e}=this.props;const t=this.props.max-this.props.min+1;return"boolean"==typeof e?e=Array.from({length:t}).map(((e,t)=>t)):"number"==typeof e&&(e=Array.from({length:t}).map(((e,t)=>t)).filter((t=>t%e==0))),e.map(parseFloat).sort(((e,t)=>e-t)).map((e=>{const t=this.calcOffset(e),n={key:e,className:this.props.markClassName,style:this.buildMarkStyle(t)};return this.props.renderMark(n)}))},n.render=function(){const e=[],{value:t}=this.state,n=t.length;for(let s=0;s<n;s+=1)e[s]=this.calcOffset(t[s],s);const s=this.props.withTracks?this.renderTracks(e):null,i=this.renderThumbs(e),r=this.props.marks?this.renderMarks():null;return o.createElement("div",{ref:e=>{this.slider=e,this.resizeElementRef.current=e},style:{position:"relative"},className:this.props.className+(this.props.disabled?" disabled":""),onMouseDown:this.onSliderMouseDown,onClick:this.onSliderClick},s,i,r)},t}(o.Component);h.displayName="ReactSlider",h.defaultProps={min:0,max:100,step:1,pageFn:e=>10*e,minDistance:0,defaultValue:0,orientation:"horizontal",className:"slider",thumbClassName:"thumb",thumbActiveClassName:"active",trackClassName:"track",markClassName:"mark",withTracks:!0,pearling:!1,disabled:!1,snapDragDisabled:!1,invert:!1,marks:[],renderThumb:e=>o.createElement("div",e),renderTrack:e=>o.createElement("div",e),renderMark:e=>o.createElement("span",e)};var d=h,m=window.wp.i18n;function f(){return f=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var s in n)({}).hasOwnProperty.call(n,s)&&(e[s]=n[s])}return e},f.apply(null,arguments)}var v=({clearUrl:e,min:t,max:n,prefix:s,suffix:i,value:o,...r})=>(window.Cypress&&(window.app={sliderChange:r.onChange}),React.createElement("div",{className:"ep-range-facet"},React.createElement("div",{className:"ep-range-facet__slider"},React.createElement(d,f({className:"ep-range-slider",minDistance:1,thumbActiveClassName:"ep-range-slider__thumb--active",thumbClassName:"ep-range-slider__thumb",trackClassName:"ep-range-slider__track",min:t,max:n,value:o},r))),React.createElement("div",{className:"ep-range-facet__values"},s,o[0],i," — ",s,o[1],i),React.createElement("div",{className:"ep-range-facet__action"},e?React.createElement("a",{href:e},(0,m.__)("Clear","elasticpress")):null," ",React.createElement("button",{className:"wp-element-button",type:"submit"},(0,m.__)("Filter","elasticpress")))));const g=({max:e,min:n})=>{const s=Math.ceil(e.max),i=Math.floor(n.min),o=e.value?parseInt(e.value,10):s,r=n.value?parseInt(n.value,10):i,[a,p]=(0,t.useState)(o),[u,l]=(0,t.useState)(r),c=(0,t.useMemo)((()=>n.dataset.prefix),[n]),h=(0,t.useMemo)((()=>n.dataset.suffix),[n]),d=(0,t.useMemo)((()=>""!==n.value?n.form.action:null),[n]);return(0,t.useLayoutEffect)((()=>{e.value=Math.min(s,a),n.value=Math.max(i,u)}),[u,a,n,e,i,s]),React.createElement(v,{clearUrl:d,max:s,min:i,prefix:c,suffix:h,onChange:([e,t])=>{p(t),l(e)},value:[u,a]})};s()((()=>{document.querySelectorAll(".ep-facet-meta-range").forEach((e=>{const[n,s]=e.querySelectorAll('input[type="hidden"]'),i=document.createElement("div");if(e.insertAdjacentElement("afterbegin",i),"function"===typeof t.createRoot){(0,t.createRoot)(i).render(React.createElement(g,{min:n,max:s}))}else(0,t.render)(React.createElement(g,{min:n,max:s}),i)}))}))}();