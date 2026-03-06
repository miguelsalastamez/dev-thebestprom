import {getThumb, isAudioType, isFolder, isImageType, isVideoType} from "./functions";

const $ = jQuery;

const DEFAULTS = {
    dynamic: true,
    dynamicEl: [],
    thumbnails: true,
    keyboard: true,
    loop: true,
    zoom: true,
    fullscreen: true,
    download: true,
    autoplayVideoOnSlide: true,
    appendSubHtmlTo: '.lg-outer',
    counter: false,
    preload: 1,
};

const Lightbox = {
    create(targetEl, opts) {
        const el = targetEl || document.body;
        const $el = $(el);
        const options = $.extend(true, {}, DEFAULTS, opts || {});
        const gallery = Array.isArray(options.dynamicEl) ? options.dynamicEl.slice() : [];
        let current = -1;
        let opened = false;

        // DOM elements
        let $backdrop, $shell, $header, $fileInfo, $controls,
            $fullBtn, $downloadBtn, $closeBtn, $stage,
            $navLeft, $navRight, $caption, $thumbbar, $loader;

        /* -------------------------------------------------------------------------- */
        /* BUILD STRUCTURE */

        /* -------------------------------------------------------------------------- */
        function build() {
            $backdrop = $('<div class="igd-lb-backdrop" aria-hidden="true"/>').appendTo('body');
            $shell = $('<div class="igd-lb-shell" role="dialog" aria-modal="true"/>').appendTo($backdrop);

            $header = $('<div class="igd-lb-header"/>').appendTo($shell);
            $fileInfo = $('<div class="igd-lb-fileinfo"/>').appendTo($header);

            $controls = $('<div class="igd-lb-controls"/>').appendTo($header);
            $fullBtn = $('<button class="igd-lb-btn igd-fullscreen" title="Fullscreen">⛶</button>').appendTo($controls);
            $downloadBtn = $('<button class="igd-lb-btn lg-download" title="Download">⬇</button>').appendTo($controls);
            $closeBtn = $('<button class="igd-lb-btn" title="Close">✕</button>').appendTo($controls);

            $stage = $('<div class="igd-lb-stage"/>').appendTo($shell);
            $navLeft = $('<button class="igd-lb-nav left" aria-label="Previous">◀</button>').appendTo($stage);
            $navRight = $('<button class="igd-lb-nav right" aria-label="Next">▶</button>').appendTo($stage);

            $caption = $('<div class="igd-lb-caption"/>').appendTo($shell);
            $thumbbar = $('<div class="igd-lb-thumbbar"/>').appendTo($shell);

            $navLeft.hide();
            $navRight.hide();
            $downloadBtn.hide();
        }

        /* -------------------------------------------------------------------------- */
        /* EVENT BINDINGS */

        /* -------------------------------------------------------------------------- */
        function bindEvents() {
            $backdrop.on('click', e => {
                if (e.target === $backdrop[0]) closeGallery();
            });

            $closeBtn.on('click', closeGallery);
            $fullBtn.on('click', toggleFullscreen);
            $navLeft.on('click', prev);
            $navRight.on('click', next);

            $downloadBtn.on('click', () => {
                const cur = gallery[current];
                if (!cur) return;
                const url = cur.downloadUrl || cur.src || '';
                if (!url) return;
                const a = document.createElement('a');
                a.href = url;
                a.rel = 'noreferrer';
                a.referrerpolicy = 'no-referrer';
                a.download = (cur.title || '').replace(/\.[^/.]+$/, '') || '';
                document.body.appendChild(a);
                a.click();
                a.remove();
            });

            $(window).on('keydown.igd', e => {
                if (!opened || !options.keyboard) return;
                if (e.key === 'Escape') closeGallery();
                if (e.key === 'ArrowLeft') prev();
                if (e.key === 'ArrowRight') next();
            });

            let startX = null;
            $stage.on('touchstart', e => {
                startX = e.originalEvent.touches ? e.originalEvent.touches[0].clientX : null;
            });
            $stage.on('touchend', e => {
                if (startX === null) return;
                const dx = e.originalEvent.changedTouches[0].clientX - startX;
                if (dx > 50) prev();
                else if (dx < -50) next();
                startX = null;
            });

            $stage.on('click', e => {
                const $target = $(e.target);
                if (!$target.is('img, video, iframe, .igd-lb-nav')) {
                    closeGallery();
                }
            });

            $thumbbar.on('click', '.igd-lb-thumb', function () {
                const idx = parseInt($(this).attr('data-index'), 10);
                if (!isNaN(idx)) show(idx);
            });
        }

        /* -------------------------------------------------------------------------- */
        /* RENDER FUNCTIONS */

        /* -------------------------------------------------------------------------- */
        function renderThumbs() {
            if (!options.thumbnails) return $thumbbar.hide();
            $thumbbar.empty().show();

            gallery.forEach((g, i) => {
                const src =
                    g.thumb ||
                    (/\.(jpg|jpeg|png|gif|webp)$/i.test(g.src)
                        ? g.src
                        : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="240" height="140"><rect width="100%" height="100%" fill="#333"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#bbb">DOC</text></svg>');
                $('<img>', {
                    class: 'igd-lb-thumb',
                    src,
                    alt: g.title || '',
                    'data-index': i,
                }).appendTo($thumbbar);
            });
        }

        function clearStage() {
            const $media = $stage.find('.igd-lb-media, .igd-lb-embed, iframe, video');
            $media.each(function () {
                try {
                    if (this.pause) this.pause();
                } catch {
                }
            });
            $stage.find('.igd-lb-embed, .igd-lb-media, iframe, video').remove();
            $caption.text('');
            $thumbbar.find('.igd-lb-thumb').removeClass('active');
        }

        function createSlide(item) {
            let $el;
            if (item.video) {
                $el = $('<video>', {
                    class: 'igd-lb-media',
                    controls: true,
                    autoplay: true,
                    playsinline: true,
                    src: item.src,
                    referrerpolicy: "no-referrer",
                });
                if (item.poster) $el.attr('poster', item.poster);
            } else if (item.audio) {
                $el = $('<audio>', {
                    class: 'igd-lb-media',
                    controls: true,
                    autoplay: true,
                    playsinline: true,
                    src: item.src,
                    referrerpolicy: "no-referrer",
                });
            } else if (item.image) {
                $el = $('<img>', {
                    class: `igd-lb-media${options.zoom ? ' igd-lb-zoom' : ''}`,
                    src: item.src,
                    alt: item.title || '',
                    referrerpolicy: 'no-referrer',
                });
            } else {
                $el = $('<iframe>', {
                    class: 'igd-lb-iframe igd-lb-embed',
                    src: item.src,
                    title: item.title || '',
                    rel: 'noopener noreferrer',
                    referrerpolicy: 'strict-origin-when-cross-origin',
                    allow: 'autoplay; fullscreen',
                    sandbox: 'allow-same-origin allow-scripts allow-popups allow-forms allow-presentation',
                });
            }
            return $el;
        }

        function enableZoom($img) {
            let scale = 1, dragging = false, startX = 0, startY = 0, translateX = 0, translateY = 0;

            const applyTransform = () => {
                $img.css('transform', `translate(${translateX}px, ${translateY}px) scale(${scale})`);
            };

            $img.css({'transform-origin': 'center center', transition: 'transform 0.1s ease-out', cursor: 'zoom-in'});

            $img.on('click', () => {
                if (scale === 1) {
                    scale = 2;
                } else {
                    scale = 1;
                    translateX = translateY = 0;
                }
                applyTransform();
            });

            $img.on('wheel', e => {
                e.preventDefault();
                const delta = e.originalEvent.deltaY > 0 ? -0.1 : 0.1;
                scale = Math.min(Math.max(1, scale + delta), 4);
                applyTransform();
            });

            $img.on('mousedown', e => {
                if (scale <= 1) return;
                dragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                $img.css('cursor', 'grabbing');
                e.preventDefault();
            });

            $(window)
                .off('.igd-zoom')
                .on('mouseup.igd-zoom', () => {
                    dragging = false;
                    $img.css('cursor', scale > 1 ? 'zoom-in' : '');
                })
                .on('mousemove.igd-zoom', e => {
                    if (!dragging) return;
                    translateX = e.clientX - startX;
                    translateY = e.clientY - startY;
                    applyTransform();
                });
        }

        /* -------------------------------------------------------------------------- */
        /* CONTROL LOGIC */

        /* -------------------------------------------------------------------------- */
        function show(index) {
            if (!gallery.length || index < 0 || index >= gallery.length) return;

            const prevIndex = current;
            clearStage();
            current = index;
            const item = gallery[index];

            $thumbbar.find(`[data-index="${index}"]`).addClass('active');
            $caption.html(item.title || '');
            $fileInfo.html(item.subHtml || '');

            $loader = $('<div class="igd-lb-loader"><div class="igd-spinner"></div></div>').appendTo($stage);
            const $slide = createSlide(item);
            $stage.append($slide);

            if (options.zoom && $slide.is('img')) enableZoom($slide);

            const hideLoader = () => {
                $loader.addClass('hide');
                setTimeout(() => $loader.remove(), 300);
            };

            if ($slide.is('img')) $slide.on('load error', hideLoader);
            else if ($slide.is('video')) $slide.on('canplay playing error', hideLoader);
            else if ($slide.is('iframe')) {
                $slide.on('load', hideLoader);
                setTimeout(hideLoader, 5000);
            } else hideLoader();

            $navLeft.toggle(gallery.length > 1);
            $navRight.toggle(gallery.length > 1);
            $downloadBtn.toggle(options.download && (item.downloadUrl || item.src));

            dispatch('lgSlideItemLoad', {index});
            dispatch('lgAfterSlide', {index, prevIndex});
        }

        function openGallery(startIndex) {
            dispatch('lgBeforeOpen', {index: startIndex});
            $backdrop.addClass('igd-open');
            $('body').css('overflow', 'hidden');
            opened = true;
            renderThumbs();
            show(isNaN(parseInt(startIndex, 10)) ? 0 : parseInt(startIndex, 10));
            dispatch('lgAfterOpen', {index: current});
        }

        function closeGallery() {
            if (!opened) return;
            dispatch('lgBeforeClose', {});
            $backdrop.removeClass('igd-open');
            clearStage();
            $('body').css('overflow', '');
            $backdrop.one('transitionend', () => {
                $backdrop.remove();
                dispatch('lgAfterClose', {});
            });
            setTimeout(() => {
                if ($backdrop && $backdrop.length) $backdrop.remove();
            }, 300);
            opened = false;
        }

        function next() {
            if (!gallery.length) return;
            let idx = current + 1;
            if (idx >= gallery.length) idx = options.loop ? 0 : current;
            show(idx);
        }

        function prev() {
            if (!gallery.length) return;
            let idx = current - 1;
            if (idx < 0) idx = options.loop ? gallery.length - 1 : current;
            show(idx);
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) $backdrop[0].requestFullscreen?.();
            else document.exitFullscreen?.();
        }

        function destroy() {
            closeGallery();
            $backdrop.remove();
            $(window).off('.igd .igd-zoom');
            $thumbbar.off();
        }

        function dispatch(name, detail) {
            try {
                const ev = new CustomEvent(name, {detail: detail || {}});
                el.dispatchEvent(ev);
            } catch {
                $el.trigger(name, detail);
            }
        }

        build();
        bindEvents();

        return {openGallery, closeGallery, next, prev, destroy, show};
    }
};

window.igdLightBox = (targetEl, options) => Lightbox.create(targetEl, options);

export default function preview(e, id, files, permissions, notifications, isGallery = false, shortcodeId = false, nonce = igd?.nonce) {

    const {
        preview = true,
        inlinePreview = true,
        allowPreviewPopout = !permissions,
        showPreviewThumbnails = !permissions,
        mediaPreview = igd?.settings?.mediaPreview || 'embed',
        download = true,
        comment,
        commentMethod,
    } = permissions || {};

    if (!preview) return;

    files = files.filter(file => !isFolder(file));

    const file = files.find(file => file.id === id);

    if (!inlinePreview) {
        //open viewLink in new tab
        if (file && file.webViewLink) {
            window.open(file.webViewLink, '_blank');
            return;
        }
    }

    const elements = files.map(file => {

        const {
            id,
            name,
            iconLink,
            accountId,
            exportAs,
            exportLinks,
            webContentLink,
            description,
        } = file;

        const isVideo = isVideoType(file);
        const isAudio = isAudioType(file);
        const isImage = isImageType(file);

        let downloadUrl = '';
        if (exportAs && download) {
            if (exportLinks || webContentLink) {
                downloadUrl = `${igd.ajaxUrl}?action=igd_download&id=${file['id']}&accountId=${file.accountId}&shortcodeId=${shortcodeId}&nonce=${nonce}`;

                const exportKeys = Object.keys(exportAs);
                if (exportKeys.length) {
                    const mimeType = exportAs[exportKeys[0]].mimetype;
                    downloadUrl += `&mimetype=${mimeType}`;
                }
            }
        }

        const element = {
            thumb: getThumb(file, 'small'),
            downloadUrl,
            subHtml: isGallery ? `<h4 class="item-name">${name.replace(/\.[^/.]+$/, "")}</h4>${!!description ? `<p>${description}</p>` : ''}`
                : `<div class="item-name">
                      <img src="${iconLink?.replace('/16/', '/32/')}" alt="${name}" />
                      <span>${name}</span>
                  </div>`,
        }

        // Comment HTML
        if (comment) {
            const baseUrl = `${window.location.protocol}//${window.location.host}${window.location.pathname}`;

            const uniqUrl = `${baseUrl}?id=${id}`;

            if (commentMethod === 'disqus') {
                element.disqusIdentifier = id;
                element.disqusUrl = uniqUrl;
            } else {
                element.fbHtml = `<div class="fb-comments" data-href="${uniqUrl}" data-width="400" data-numposts="5"></div>`;
            }

        }

        if (mediaPreview === 'direct' && (isVideo || isAudio || isImage)) {
            if (isVideo) {
                element.video = true;
                element.poster = getThumb(file, 'large');
                element.src = `${igd.ajaxUrl}?action=igd_stream&id=${id}&account_id=${accountId}&shortcodeId=${shortcodeId}&nonce=${nonce}`;
            } else if (isAudio) {
                element.audio = true;
                element.src = `${igd.ajaxUrl}?action=igd_stream&id=${id}&account_id=${accountId}&shortcodeId=${shortcodeId}&nonce=${nonce}`;
            } else if (isImage) {
                element.image = true;
                element.src = getThumb(file, 'full');
            }
        } else {
            element.iframe = true;
            element.src = `${igd.ajaxUrl}?action=igd_preview&file_id=${id}&account_id=${accountId}&shortcodeId=${shortcodeId}&popout=${allowPreviewPopout}&nonce=${nonce}`;
        }


        return element;

    });


    const isMobile = window.innerWidth < 768;

    const options = {
        dynamic: true,
        dynamicEl: elements,
        addClass: `igd-lightbox ${!download ? 'no-download' : ''} ${isGallery ? 'gallery-lightbox' : ''} ${allowPreviewPopout ? 'allow-popout' : ''} `,
        counter: isGallery,
        autoplayVideoOnSlide: true,
        preload: 2,
        mobileSettings: {
            showCloseIcon: true,
            thumbWidth: 60,
            thumbHeight: 60,
            download: true,
            controls: true,
        },
        animateThumb: true,
        zoomFromOrigin: true,
        toggleThumb: true,
    }

    // const plugins = [lgZoom, lgVideo, lgRotate];
    //
    // if (comment) {
    //     plugins.push(lgComment);
    // }
    //
    // if (!isMobile) {
    //     plugins.push(lgFullscreen);
    // }
    //
    // if (showPreviewThumbnails) {
    //     plugins.push(lgThumbnail);
    // }
    //
    // options.plugins = plugins;
    //
    // if (isGallery) {
    //     options.allowMediaOverlap = true;
    // } else {
    //     options.appendSubHtmlTo = '.lg-outer';
    // }

    // if (comment) {
    //     options.commentBox = true;
    //
    //     if (commentMethod === 'disqus') {
    //         options.disqusComments = true;
    //     } else {
    //         options.fbComments = true;
    //     }
    // }

    e.target.addEventListener('lgAfterSlide', (e) => {
        const {index, prevIndex} = e.detail;

        if (igd.settings.enableStatistics) {

            const file = files[index];

            const {id, accountId, name, type} = file;

            wp.ajax.post('igd_log', {
                file_id: id,
                account_id: accountId,
                file_name: name,
                file_type: type,
                type: 'preview',
                nonce,
            });
        }

    });

    // Check if the download button is clicked
    // Send download notification
    if (notifications && notifications.downloadNotification) {
        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('lg-download')) {
                wp.ajax.post('igd_notification', {
                    files: [file],
                    notifications,
                    type: 'download',
                    nonce: igd.nonce,
                });
            }
        }, false);
    }

    const index = files.findIndex(file => file.id === id);

    igdLightBox(e.target, options).openGallery(index);
}
