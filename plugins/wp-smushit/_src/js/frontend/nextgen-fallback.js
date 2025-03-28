(function () {
	'use strict';

	// Source: https://developers.google.com/speed/webp/faq#in_your_own_javascript.
	function check_feature(feature, callback) {
		const testImages = {
			webp: "data:image/webp;base64,UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA==",
			avif: "data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgANogQEAwgMg8f8D///8WfhwB8+ErK42A="
		};
		const img = new Image();
		img.onload = function () {
			const result = (img.width > 0) && (img.height > 0);
			callback(result);
		};
		img.onerror = function () {
			callback(false);
		};
		img.src = testImages[feature];
	}

	function make_callback(fallbackAttributeName, dataValueName, extension) {
		return function (isNextGenSupported) {
			document.documentElement.classList.add(isNextGenSupported ? extension : 'no-' + extension);
			if (isNextGenSupported) {
				return;
			}

			const originalGetAttribute = Object.getOwnPropertyDescriptor(Element.prototype, 'getAttribute');

			// Redefine the getAttribute function with a custom implementation
			Object.defineProperty(Element.prototype, 'getAttribute', {
				value: function (attributeName) {
					if (!this.dataset.hasOwnProperty(dataValueName)) {
						return originalGetAttribute.value.call(this, attributeName);
					}

					const fallbackObject = JSON.parse(this.dataset[dataValueName]);

					if (attributeName in fallbackObject) {
						return fallbackObject[attributeName];
					}

					return originalGetAttribute.value.call(this, attributeName);
				}
			});

			const elementsWithFallback = document.querySelectorAll('[' + fallbackAttributeName + ']:not(.lazyload)');
			if (elementsWithFallback.length) {
				// Update background image, src, srcset.
				const imageDisplayAttrs = ['src', 'srcset'];
				elementsWithFallback.forEach((element) => {
					const fallbackObject = JSON.parse(element.dataset[dataValueName]);
					imageDisplayAttrs.forEach(function (attrName) {
						if (attrName in fallbackObject) {
							element.setAttribute(attrName, fallbackObject[attrName]);
						}
					});

					// Update background image.
					if ('bg' in fallbackObject) {
						element.style.background = fallbackObject.bg;
					}
					if ('bg-image' in fallbackObject) {
						element.style.backgroundImage = fallbackObject['bg-image'];
					}
				});
			}
		};
	}

	if (wp_smushit_nextgen_data?.mode === 'avif') {
		check_feature('avif', make_callback('data-smush-avif-fallback', 'smushAvifFallback', 'avif'));
	} else {
		check_feature('webp', make_callback('data-smush-webp-fallback', 'smushWebpFallback', 'webp'));
	}
})();
