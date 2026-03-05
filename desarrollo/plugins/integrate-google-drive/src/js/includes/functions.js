export function getMimeType(mime, returnGroup = false) {

    const mimes = {

        text: {
            'application/vnd.oasis.opendocument.text': 'Text',
            'text/plain': 'Text',
        },

        file: {
            'text/html': 'HTML',
            'text/php': 'PHP',
            'x-httpd-php': 'PHP',
            'text/css': 'CSS',
            'text/js': 'JavaScript',
            'application/javascript': 'JavaScript',
            'application/json': 'JSON',
            'application/xml': 'XML',
            'application/x-shockwave-flash': 'SWF',
            'video/x-flv': 'FLV',
            'application/vnd.google-apps.file': 'File',
        },

        // images
        image: {
            'application/vnd.google-apps.photo': 'Photo',
            'image/png': 'PNG',
            'image/jpeg': 'JPEG',
            'image/jpg': 'JPG',
            'image/gif': 'GIF',
            'image/bmp': 'BMP',
            'image/vnd.microsoft.icon': 'ICO',
            'image/tiff': 'TIFF',
            'image/tif': 'TIF',
            'image/svg+xml': 'SVG',
        },

        // archives
        zip: {
            'application/zip': 'ZIP',
            'application/x-rar-compressed': 'RAR',
            'application/x-msdownload': 'EXE',
            'application/vnd.ms-cab-compressed': 'CAB',
        },

        // audio/video
        audio: {
            'audio/mpeg': 'MP3',
            'video/quicktime': 'QT',
            'application/vnd.google-apps.audio': 'Audio',
            'audio/x-m4a': 'Audio',
        },

        video: {
            'application/vnd.google-apps.video': 'Video',
            'video/x-flv': 'Video',
            'video/mp4': 'Video',
            'video/webm': 'Video',
            'video/ogg': 'Video',
            'application/x-mpegURL': 'Video',
            'video/MP2T': 'Video',
            'video/3gpp': 'Video',
            'video/quicktime': 'Video',
            'video/x-msvideo': 'Video',
            'video/x-ms-wmv': 'Video',
        },

        // adobe
        pdf: {
            'application/pdf': 'PDF',
        },

        // ms office
        word: {
            'application/msword': 'MS Word',
        },

        doc: {
            'application/vnd.google-apps.document': 'Google Docs',
        },

        excel: {
            'application/vnd.ms-excel': 'Excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'Excel',
        },

        presentation: {
            'application/vnd.google-apps.presentation': 'Slide',
            'application/vnd.oasis.opendocument.presentation': 'Presentation'
        },

        powerpoint: {
            'application/vnd.ms-powerpoint': 'Powerpoint',
        },

        form: {
            'application/vnd.google-apps.form': 'Form',
        },

        folder: {
            'application/vnd.google-apps.folder': 'Folder',
        },

        drawing: {
            'application/vnd.google-apps.drawing': 'Drawing',
        },

        script: {
            'application/vnd.google-apps.script': 'Script',
        },

        sites: {
            'application/vnd.google-apps.sites': 'Sites',
        },

        spreadsheet: {
            'application/vnd.google-apps.spreadsheet': 'Spreadsheet',
            'application/vnd.oasis.opendocument.spreadsheet': 'Spreadsheet',
        }
    }

    let fileType = 'File';
    let groupType = 'file';

    Object.keys(mimes).map(group => {

        if (returnGroup && mimes[group][mime]) {
            groupType = group;
        } else {
            if (mimes[group][mime]) {
                fileType = mimes[group][mime];
            }
        }

    })

    return returnGroup ? groupType : fileType;

}

export function humanFileSize(size) {
    if (size < 1) {
        return 0;
    }

    const i = Math.floor(Math.log(size) / Math.log(1024));
    return (size / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['Byte', 'KB', 'MB', 'GB', 'TB'][i];
}

export function humanDuration(milliseconds) {
    const totalSeconds = Math.floor(milliseconds / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = Math.floor(totalSeconds % 60);

    // Format hours, minutes, and seconds as 2-digit numbers
    const formattedHours = hours > 0 ? String(hours).padStart(2, '0') + ':' : '';
    const formattedMinutes = String(minutes).padStart(2, '0');
    const formattedSeconds = String(seconds).padStart(2, '0');

    return `${formattedHours}${formattedMinutes}:${formattedSeconds}`;
}

export function isFolder(item) {
    if (!item || !item.type) {
        return true;
    }

    if (item.type === 'application/vnd.google-apps.folder') {
        return true;
    }

    if (item.shortcutDetails?.targetMimeType === 'application/vnd.google-apps.folder') {
        return true;
    }

    return false;
}

export function isShortcut(mimeType) {
    return mimeType === 'application/vnd.google-apps.shortcut';
}

export function getTypeIcon(type) {
    let group = getMimeType(type, true);
    group = group ? group : 'file';

    return `${igd.pluginUrl}/assets/images/icons/${group}.png`;
}

export function getRootFolders(folderId = null, account = igd.activeAccount) {
    const accountId = account.id;

    const items = [
        {
            accountId,
            id: 'root',
            name: wp.i18n.__('My Drive', 'integrate-google-drive'),
            type: 'application/vnd.google-apps.folder',
            iconLink: `${igd.pluginUrl}/assets/images/my-drive.svg`,
        },
        {
            accountId,
            id: 'shared-drives',
            name: wp.i18n.__('Shared Drives', 'integrate-google-drive'),
            type: 'application/vnd.google-apps.folder',
            iconLink: `${igd.pluginUrl}/assets/images/shared-drives.svg`,
        },
        {
            accountId,
            id: 'computers',
            name: wp.i18n.__('Computers', 'integrate-google-drive'),
            type: 'application/vnd.google-apps.folder',
            iconLink: `${igd.pluginUrl}/assets/images/computers.svg`,
        },
        {
            accountId,
            id: 'shared',
            name: wp.i18n.__('Shared with me', 'integrate-google-drive'),
            type: 'application/vnd.google-apps.folder',
            iconLink: `${igd.pluginUrl}/assets/images/shared.svg`,
        },
        {
            accountId,
            id: 'starred',
            name: wp.i18n.__('Starred', 'integrate-google-drive'),
            type: 'application/vnd.google-apps.folder',
            iconLink: `${igd.pluginUrl}/assets/images/starred.svg`,
        },

    ];

    if (folderId) {
        return items.find(item => item['id'] === folderId);
    }

    return items;
}

export function getFileRelativePath(file) {
    const relativePath = file?.getSource?.()?.relativePath?.replace(/^\//, '');

    return relativePath ?? '';
}

export function isAudioVideoType(item) {
    return isAudioType(item) || isVideoType(item);
}

export function isVideoType(item) {
    if (!item) {
        return false;
    }

    if (item?.type?.includes('video/')) {
        return true;
    }

    if (item.shortcutDetails?.targetMimeType.includes('video/')) {
        return true;
    }

    return false;
}

export function isAudioType(item) {
    if (!item) {
        return false;
    }

    if (item.type?.includes('audio/')) {
        return true;
    }

    if (item.shortcutDetails?.targetMimeType.includes('audio/')) {
        return true;
    }

    return false;
}

export function isImageType(item) {
    if (!item) {
        return false;
    }

    if (item.type?.includes('image/')) {
        return true;
    }

    if (item.shortcutDetails?.targetMimeType.includes('image/')) {
        return true;
    }

    return false;
}

export function isRootFolder(folderId, account) {
    return getRootFolders(folderId, account);
}

export function updateParam(param) {
    const key = Object.keys(param)[0];
    const value = param[key];

    function addOrReplaceParam(param, value) {
        const url = window.location.href;

        const stringToAdd = `${param}=` + value;

        const has_param = url.match(/\?./);

        if (window.location.search === "") {
            return `${url}${has_param ? '&' : '?'}${stringToAdd}`;
        }

        if (window.location.search.indexOf(`${param}=`) === -1) {
            return `${url}${has_param ? '&' : '?'}${stringToAdd}`;
        }

        const searchParams = window.location.search.substring(1).split("&");

        for (let i = 0; i < searchParams.length; i++) {
            if (searchParams[i].indexOf(`${param}=`) > -1) {
                searchParams[i] = `${param}=` + value;
                break;
            }
        }

        return url.split("?")[0] + "?" + searchParams.join("&");
    }

    history?.replaceState(history.state, null, addOrReplaceParam(key, value));
}

export function useHandleListingResize(ref, isList) {
    const [listClass, setListClass] = React.useState(isList ? 'igd-item-col-1' : 'igd-item-col-4');

    // Flexbox breakpoints
    const screens = React.useMemo(() => [
        {class: 'igd-item-col-1', width: 320},
        {class: 'igd-item-col-2', width: 480},
        {class: 'igd-item-col-3', width: 768},
        {class: 'igd-item-col-4', width: 992},
        {class: 'igd-item-col-5', width: 1200},
        {class: 'igd-item-col-6', width: 1440},
        {class: 'igd-item-col-7', width: 1680},
        {class: 'igd-item-col-8', width: 1920},
        {class: 'igd-item-col-9', width: Infinity}, // Ultra-wide screens
    ], []);

    function handleListingResize(entries) {
        if (isList) {
            setListClass('igd-item-col-1');
            return;
        }

        const width = entries[0]?.contentRect?.width;

        if (!width) return;

        // Find the appropriate class based on the width
        const matchingBreakpoint = screens.find(screen => width < screen.width) || screens[screens.length - 1];
        setListClass(matchingBreakpoint.class);
    }

    React.useEffect(() => {
        if (!ref?.current) return;

        const resizeObserver = new ResizeObserver(handleListingResize);
        resizeObserver.observe(ref.current);

        return () => {
            resizeObserver.disconnect();
        };
    }, [ref, isList]);

    return listClass;
}

export function moduleTypes() {
    return {

        browser: {
            title: wp.i18n.__('File Browser', 'integrate-google-drive'),
            description: wp.i18n.__('Allow users to browse selected Google Drive files and folders directly on your site.', 'integrate-google-drive'),
            isPro: true,
        },

        gallery: {
            title: wp.i18n.__('Gallery', 'integrate-google-drive'),
            description: wp.i18n.__('Showcase images and videos in a responsive masonry grid with lightbox previews.', 'integrate-google-drive'),
        },

        review: {
            title: wp.i18n.__('Review & Approve', 'integrate-google-drive'),
            description: wp.i18n.__('Allow users to review, select, and confirm their Google Drive file choices.', 'integrate-google-drive'),
            isPro: true,
        },

        uploader: {
            title: wp.i18n.__('File Uploader', 'integrate-google-drive'),
            description: wp.i18n.__('Let users upload files directly to a specific Google Drive folder.', 'integrate-google-drive'),
            isPro: true,
        },

        media: {
            title: wp.i18n.__('Media Player', 'integrate-google-drive'),
            description: wp.i18n.__('Stream audio and video files from Google Drive using a built-in media player.', 'integrate-google-drive'),
            isPro: true,
        },

        search: {
            title: wp.i18n.__('Search Box', 'integrate-google-drive'),
            description: wp.i18n.__('Enable users to quickly search files and folders within your connected Google Drive.', 'integrate-google-drive'),
            isPro: true,
        },

        embed: {
            title: wp.i18n.__('Embed Documents', 'integrate-google-drive'),
            description: wp.i18n.__('Easily embed Google Drive documents into your content.', 'integrate-google-drive'),
        },

        list: {
            title: wp.i18n.__('List', 'integrate-google-drive'),
            description: wp.i18n.__('List the Google Drive files with view and download options', 'integrate-google-drive'),
        },

        slider: {
            title: wp.i18n.__('Slider Carousel', 'integrate-google-drive'),
            description: wp.i18n.__('Display images, videos, and documents in a smooth, touch-friendly carousel slider.', 'integrate-google-drive'),
            isPro: true,
        },

    }
}

export function copyShortcode(id) {
    const shortcode = `[integrate_google_drive id=${id}]`;

    if (window.isSecureContext && navigator.clipboard) {
        navigator.clipboard.writeText(shortcode);
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = shortcode;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("Copy");
        textArea.remove();
    }

    Swal.fire({
        title: wp.i18n.__('Copied', "integrate-google-drive"),
        text: wp.i18n.__('Shortcode copied to clipboard', "integrate-google-drive"),
        icon: 'success',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
        toast: true,
    });
}

export function copyViewUrl(id) {
    const viewUrl = `${igd.homeUrl}/igd-modules/${id}`;

    if (window.isSecureContext && navigator.clipboard) {
        navigator.clipboard.writeText(viewUrl);
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = viewUrl;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("Copy");
        textArea.remove();
    }

    Swal.fire({
        title: wp.i18n.__("Copied!", "integrate-google-drive"),
        text: wp.i18n.__("View URL copied to clipboard.", "integrate-google-drive"),
        icon: 'success',
        customClass: {container: 'igd-swal'},
        timer: 2000,
    });
}

export function loadAvatar(currentTarget, email) {
    currentTarget.onerror = null;
    const src = 'https://www.gravatar.com/avatar/' + md5(email) + '?s=200&d=mm';

    if (currentTarget.src === src) {
        currentTarget.src = 'data:image/jpeg;base64,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';
    } else {
        currentTarget.src = src;
    }
}

export function getThumb(file, size = 'default', customSize = {}, force = false) {
    let {id, iconLink, thumbnailLink, shortcutDetails} = file;

    const width = customSize?.w || 64;
    const height = customSize?.h || 64;

    if (shortcutDetails?.targetId) {
        id = shortcutDetails.targetId;
    }

    const isPublic = isFilePublic(file);

    const sizeMap = {
        custom: `w${width}-h${height}`,
        small: 'w300-h300',
        medium: 'w600-h400',
        large: 'w1024-h768',
        full: 'w2048',
        default: 'w300-h300',
    };

    const privateSizeMap = {
        custom: `=w${width}-h${height}`,
        small: '=w300-h300',
        medium: '=h600-nu',
        large: '=w1024-h768-p-k-nu',
        full: '=w2048',
        default: '=w200-h190-p-k-nu',
    };

    if (thumbnailLink) {

        if (isPublic && !force) {
            const sz = sizeMap[size] || sizeMap.default;
            return `https://drive.google.com/thumbnail?id=${id}&sz=${sz}`;
        }

        if (!force && !thumbnailLink.includes('google.com')) {
            const replacement = privateSizeMap[size] || privateSizeMap.default;
            return thumbnailLink.replace(/=s\d+/, replacement);
        }

        return getPrivateImageUrl(file, size, width, height); // fallback or proxy
    }

    return iconLink?.replace('/16/', `/${width}/`) || '';
}

export function getPrivateImageUrl(file, size, width = 300, height = 300) {
    const {id, accountId} = file;

    const params = new URLSearchParams({
        igd_preview_image: 1,
        id,
        account_id: accountId,
        size,
        width: width,
        height: height,
    });

    return `${igd.homeUrl}/?${params.toString()}`;
}

export function isFilePublic(file) {
    const role = file.permissions?.users?.anyoneWithLink?.role;
    return role === 'reader' || role === 'writer';
}

export function useMounted() {
    const [isMounted, setIsMounted] = React.useState(false)

    React.useEffect(() => {
        setIsMounted(true)
    }, [])

    return isMounted
}

export function initShortcode() {
    const {IgdShortcode} = window;

    Array.from(document.querySelectorAll('.igd')).forEach(element => {
        const data = element.dataset.shortcodeData;

        if (data) {
            try {
                const parsedData = JSON.parse(base64Decode(data));

                ReactDOM.render(<IgdShortcode data={parsedData}/>, element);
            } catch (error) {
                console.error('Could not parse the shortcode data', error);
            }
        }
    });
}

export function base64Encode(str) {
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
        function toSolidBytes(match, p1) {
            return String.fromCharCode('0x' + p1);
        }));
}

export function base64Decode(str) {
    return decodeURIComponent(atob(str).split('').map(function (c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
}

export function isHomeFolder(folderId, account = igd.activeAccount) {
    return 'root' !== folderId && isRootFolder(folderId, account);
}

export function hexToRgb(hex) {
    let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

export function openAddAccountWindow(width = '970', height = '850') {
    // Calculate the position of the new window
    const screenLeft = window.screenLeft || window.screenX;
    const screenTop = window.screenTop || window.screenY;
    const screenWidth = window.innerWidth || document.documentElement.clientWidth || screen.width;
    const screenHeight = window.innerHeight || document.documentElement.clientHeight || screen.height;

    const left = screenLeft + (screenWidth / 2) - (width / 2);
    const top = screenTop + (screenHeight / 2) - (height / 2);

    // Open the new window
    const newWindow = window.open(igd.authUrl, 'newwindow', `width=${width},height=${height},left=${left},top=${top}`);

    // Bring the new window to the front
    if (newWindow.focus) {
        newWindow.focus();
    }

    removeAllLastFolderSessions();
}

export function useLazyLoad(ref, getFiles, isLoading, activeFolder, isSearchResults, isShortcodeBuilder, hideAll) {

    function getParentWithScrollbar(element) {
        let scrollableParent = element.parentElement;
        let result = null;

        while (scrollableParent) {
            if (scrollableParent.nodeName.toLowerCase() === 'div' &&
                (scrollableParent.scrollHeight > scrollableParent.clientHeight || scrollableParent.scrollWidth > scrollableParent.clientWidth)) {
                result = scrollableParent;
            }
            scrollableParent = scrollableParent.parentElement;
        }

        return result;
    }


    React.useEffect(() => {
        const container = ref.current;

        if (!container || isLoading || isSearchResults || !activeFolder || !activeFolder.pageNumber || activeFolder.pageNumber < 1) return;

        // Don't lazy load in editors
        const isGutenbergEditor = document.body.classList.contains('block-editor-page');
        const isElementorEditor = document.body.classList.contains('elementor-editor-active');
        const isDiviEditor = document.body.classList.contains('et-fb');
        const isEditor = isGutenbergEditor || isElementorEditor || isDiviEditor;

        if (isEditor && !isShortcodeBuilder) return;

        const parentContainer = getParentWithScrollbar(container);

        let previousScrollPosition = parentContainer ? parentContainer.scrollTop : window.pageYOffset;

        const lazyLoad = () => {

            const currentScrollPosition = parentContainer ? parentContainer.scrollTop : window.pageYOffset;
            const isScrollingDown = currentScrollPosition > previousScrollPosition;
            previousScrollPosition = currentScrollPosition;

            if (!isScrollingDown) return;

            if (parentContainer) {
                const scrollHeight = parentContainer.scrollHeight;
                const clientHeight = parentContainer.clientHeight;

                if (scrollHeight - currentScrollPosition <= clientHeight) {
                    getFiles(activeFolder, 'lazy');
                }
            } else {
                const rect = container.getBoundingClientRect();
                const windowHeight = window.innerHeight || document.documentElement.clientHeight;

                if (rect.bottom <= windowHeight + 5) {
                    getFiles(activeFolder, 'lazy');
                }
            }
        };

        const target = parentContainer ? parentContainer : window;
        target.addEventListener('scroll', lazyLoad);

        return () => {
            target.removeEventListener('scroll', lazyLoad);
        };

    }, [ref, getFiles, isLoading, activeFolder]);
}

export function getListingView(shortcode_id) {
    return localStorage.getItem(`igd_listing_view_${shortcode_id}`);
}

export function setListingView(shortcode_id, view) {
    localStorage.setItem(`igd_listing_view_${shortcode_id || 'admin'}`, view);
}

export function setLastFolderSession(folder, shortcodeId = 'admin') {
    if (folder) {
        sessionStorage.setItem(`igd_last_folder_${shortcodeId}`, JSON.stringify({...folder, pageNumber: 0}));
    }
}

export function removeLastFolderSession(shortcodeId = 'admin') {
    sessionStorage.removeItem(`igd_last_folder_${shortcodeId}`);
}

export function removeAllLastFolderSessions() {
    Object.keys(sessionStorage).forEach(key => {
        if (key.includes('igd_last_folder_')) {
            sessionStorage.removeItem(key);
        }
    });
}

export function getSessionLastFolder(shortcodeId = 'admin', initParentFolder) {
    let lastFolder = null;

    const isElementorEditor = document.body.classList.contains('elementor-editor-active');
    const isDiviEditor = document.body.classList.contains('et-fb');
    const isGutenbergEditor = document.body.classList.contains('block-editor-page');

    if (isElementorEditor || isDiviEditor || isGutenbergEditor) return lastFolder;

    let lastFolderSession = sessionStorage.getItem(`igd_last_folder_${shortcodeId}`);

    if (lastFolderSession) {
        lastFolder = JSON.parse(lastFolderSession);

        if (lastFolder.id === initParentFolder?.id) {
            lastFolder = null;
        }
    }

    return lastFolder;
}

export function formatDate(date, includeTime = false) {
    date = new Date(date);
    const year = date.getFullYear();
    const month = ('0' + (date.getMonth() + 1)).slice(-2); // Add 1 because getMonth() starts at 0
    const day = ('0' + date.getDate()).slice(-2);

    if (includeTime) {
        const hour24 = date.getHours();
        const amPm = hour24 < 12 ? 'AM' : 'PM';
        const hour12 = hour24 === 0 ? 12 : (hour24 > 12 ? hour24 - 12 : hour24); // Convert to 12-hour format
        const minutes = ('0' + date.getMinutes()).slice(-2);
        return `${year}-${month}-${day} ${('0' + hour12).slice(-2)}:${minutes} ${amPm}`;
    } else {
        return `${year}-${month}-${day}`;
    }
}

export function timeAgo(date) {
    const now = new Date();
    const diff = Math.abs(now - new Date(date));
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days > 7) {
        return formatDate(date);
    } else {
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor(diff / (1000 * 60));

        if (days > 0) {
            return days + ' day' + (days > 1 ? 's' : '') + ' ago';
        } else if (hours > 0) {
            return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
        } else {
            return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
        }
    }
}

export function setIdParam(id) {
    const url = new URL(window.location.href);
    url.searchParams.set('id', id);
    window.history.pushState({}, '', url);
}

export function removeIdParam() {
    const url = new URL(window.location.href);
    url.searchParams.delete('id');
    window.history.pushState({}, '', url);
}

export function getIdParam() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    return id;
}

export async function showReviewPopup() {
    const lastReviewPopup = localStorage.getItem('igd_last_review_popup');

    let remindInDays = Number(localStorage.getItem('igd_remind_in_days')) || 2;

    const currentDate = new Date().getTime();
    const intervalMilliseconds = remindInDays * 24 * 60 * 60 * 1000;

    // If the popup has never been shown, or it's been more than the interval since it was last shown
    if (lastReviewPopup && (currentDate - lastReviewPopup) <= intervalMilliseconds) return;

    localStorage.setItem('igd_last_review_popup', new Date().getTime()); // save the current date as the last shown date

    const result = await Swal.fire({
        title: wp.i18n.__('Are You Enjoying This Plugin?', 'integrate-google-drive'),
        text: wp.i18n.__("Your feedback helps us create a better experience for you.", 'integrate-google-drive'),
        icon: 'question',
        showDenyButton: true,
        confirmButtonText: wp.i18n.__(`Yes, I'm enjoying it`, 'integrate-google-drive'),
        denyButtonText: wp.i18n.__(`Not really`, 'integrate-google-drive'),
        reverseButtons: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCloseButton: true,
        customClass: {container: 'igd-swal igd-review-swal'},
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: wp.i18n.__('We\'re glad to hear that!', 'integrate-google-drive'),
                text: wp.i18n.__('Would you mind taking a few minutes to rate us and write a review?', 'integrate-google-drive'),
                icon: 'success',
                showDenyButton: true,
                confirmButtonText: wp.i18n.__('Sure, I\'d be happy to', 'integrate-google-drive'),
                denyButtonText: wp.i18n.__('Maybe later', 'integrate-google-drive'),
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {container: 'igd-swal igd-review-swal'},
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open('https://wordpress.org/support/plugin/integrate-google-drive/reviews/?filter=5#new-post', '_blank');

                    wp.ajax.post('igd_hide_review_notice', {
                        nonce: igd.nonce,
                    });
                } else if (result.isDenied) {
                    localStorage.setItem('igd_remind_in_days', 10);
                }
            })
        } else if (result.isDenied) {
            Swal.fire({
                title: wp.i18n.__('Sorry to hear that!', 'integrate-google-drive'),
                text: wp.i18n.__('Could you please provide us with some feedback to help us improve?', 'integrate-google-drive'),
                input: 'textarea',
                inputPlaceholder: wp.i18n.__('Enter your feedback here...', 'integrate-google-drive'),
                showCancelButton: false,
                confirmButtonText: wp.i18n.__('Submit', 'integrate-google-drive'),
                showLoaderOnConfirm: true,
                showCloseButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {container: 'igd-swal igd-review-swal'},
            }).then((result) => {

                if (result.isConfirmed) {
                    Swal.fire({
                        title: wp.i18n.__('Thank you for your feedback!', 'integrate-google-drive'),
                        text: wp.i18n.__("We'll use your feedback to improve our plugin.", 'integrate-google-drive'),
                        icon: 'info',
                        customClass: {container: 'igd-swal igd-review-swal'},
                    });

                    wp.ajax.post('igd_review_feedback', {
                        feedback: result.value,
                        nonce: igd.nonce,
                    });
                } else if (result.isDismissed) {
                    wp.ajax.post('igd_hide_review_notice', {
                        nonce: igd.nonce,
                    });
                }

            })
        }
    });

}

export function decodeHTML(html) {
    const txt = document.createElement('textarea');
    txt.innerHTML = html;
    return txt.value;
}

export function isTouchScreen() {
    return ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);
}

export function deleteFiles({
                                files,
                                activeFiles,
                                setFiles,
                                setAllFiles,
                                activeFolder,
                                setActiveFiles,
                                activeFile,
                                notifications,
                                shortcodeId,
                                nonce = igd.nonce,
                            }) {

    const fileIds = activeFiles.length ? activeFiles.map(item => item.id) : [activeFile.id];
    const accountId = activeFiles.length ? activeFiles[0].accountId : activeFile.accountId;

    Swal.fire({
        title: wp.i18n.__('Are you sure?', 'integrate-google-drive'),
        text: wp.i18n.sprintf(wp.i18n._n('You are about to delete the file.', 'You are about to delete %s files.', fileIds.length, 'integrate-google-drive'), fileIds.length),
        icon: 'warning',
        showCancelButton: true,
        customClass: {container: 'igd-swal igd-swal-reverse'},
        confirmButtonText: wp.i18n.__('Yes, delete it!', 'integrate-google-drive'),
        cancelButtonText: wp.i18n.__('No, cancel!', 'integrate-google-drive'),
        reverseButtons: true,
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return wp.ajax.post('igd_delete_files', {
                shortcodeId,
                file_ids: fileIds,
                account_id: accountId,
                nonce,
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {

            // Send delete notification
            if (notifications && notifications.deleteNotification) {
                wp.ajax.post('igd_notification', {
                    files: activeFiles,
                    notifications,
                    type: 'delete',
                    nonce,
                });
            }

            const items = files.filter(file => !fileIds.includes(file.id));

            setFiles(items);
            setAllFiles(prevFiles => ({...prevFiles, [activeFolder.id]: items}));
            setActiveFiles([]);

            Swal.fire({
                title: wp.i18n.__('Deleted!', 'integrate-google-drive'),
                text: wp.i18n.sprintf(wp.i18n._n('File has been deleted.', '%d files have been deleted.', fileIds.length, 'integrate-google-drive'), fileIds.length),
                icon: 'success',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
                toast: true,
                customClass: {container: 'igd-swal'},
            });
        }
    });
}

export function handleDownload(isOptions, activeFiles, activeFile, permissions, shortcodeId, nonce) {
    const uniqueId = (new Date()).getTime();

    const fileIds = isOptions ? activeFiles.filter(file => !permissions || !isFolder(file) || permissions.folderDownload).map(item => item.id) : [activeFile?.id];

    const accountId = isOptions ? activeFiles[0]['accountId'] : activeFile['accountId'];

    const downloadLink = `${igd.ajaxUrl}?action=igd_download&file_ids=${base64Encode(JSON.stringify(fileIds))}&request_id=${uniqueId}&accountId=${accountId}&shortcodeId=${shortcodeId}&nonce=${nonce || igd.nonce}`;


    Swal.fire({
        title: wp.i18n.__('Download', 'integrate-google-drive'),
        html: `<div class="igd-download-wrap"><div id="igd-download-status"></div><div id="igd-download-progress"></div><iframe id="igd-hidden-download" class="igd-hidden" src="${downloadLink}" ></iframe></div>`,
        showCancelButton: false,
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        customClass: {container: 'igd-swal igd-download-popup',},
        didOpen: () => {
            Swal.showLoading();

            const statusElement = jQuery('#igd-download-status');
            const progressElement = jQuery('#igd-download-progress');

            window.downloadStatusInterval = setInterval(() => {

                wp.ajax.post('igd_download_status', {
                    id: uniqueId,
                    nonce: nonce || igd.nonce,
                }).done((response) => {

                    if (!document.getElementById('igd-hidden-download')) {
                        clearInterval(window.downloadStatusInterval);

                        setTimeout(() => {
                            Swal.close();
                        }, 300);

                        return;
                    }

                    if (!response) {
                        clearInterval(window.downloadStatusInterval);

                        setTimeout(() => {
                            Swal.fire({
                                title: wp.i18n.__('Completed!', 'integrate-google-drive'),
                                text: wp.i18n.__('Download completed', 'integrate-google-drive'),
                                icon: 'success',
                                showConfirmButton: false,
                                toast: true,
                                timer: 2000,
                                timerProgressBar: true,
                                customClass: {container: 'igd-swal'},
                            });
                        }, 300);

                        return;
                    }

                    const {status, total, downloaded, action} = response;

                    if ('failed' === action) {
                        clearInterval(window.downloadStatusInterval);

                        setTimeout(() => {
                            Swal.fire({
                                title: wp.i18n.__('Error!', 'integrate-google-drive'),
                                text: status,
                                icon: 'error',
                                showConfirmButton: true,
                                confirmButtonText: wp.i18n.__('OK', 'integrate-google-drive'),
                                customClass: {container: 'igd-swal'},
                            });
                        }, 300);

                    } else {
                        statusElement.html(status);

                        if ('downloading' === action) {
                            progressElement.html(`${humanFileSize(downloaded)} of ${humanFileSize(total)} - ${Math.round(downloaded / total * 100)}%`);
                        }
                    }

                }).fail((error) => {
                    clearInterval(window.downloadStatusInterval);

                    Swal.fire({
                        title: wp.i18n.__('Error!', 'integrate-google-drive'),
                        text: error.message,
                        icon: 'error',
                        showConfirmButton: true,
                        confirmButtonText: wp.i18n.__('OK', 'integrate-google-drive'),
                        customClass: {container: 'igd-swal'},
                    });
                });

            }, 1500);
        },

        willClose() {
            clearInterval(window.downloadStatusInterval);
        }
    });

}

export function sortFiles(files, sort = {}) {
    if (Object.keys(sort).length === 0) {
        sort = {sortBy: 'name', sortDirection: 'asc'};
    }

    const sortBy = sort.sortBy;
    const sortDirection = sort.sortDirection === 'asc' ? 1 : -1;
    const isRandom = sortBy === 'random';

    // Initializing sorting arrays
    let sortArray = [];
    let sortArraySecondary = [];

    // Populating sorting arrays and adding isFolder attribute to files
    files = files.map((file) => {
        const fileCopy = {...file};

        if (!fileCopy[sortBy]) {
            sortArraySecondary.push(0);
            fileCopy[sortBy] = '';
        } else {
            sortArraySecondary.push(
                ['created', 'updated'].includes(sortBy)
                    ? new Date(fileCopy[sortBy]).getTime()
                    : fileCopy[sortBy]
            );
        }

        fileCopy.isFolder = isFolder(fileCopy); // Assuming `igdIsDir` is defined elsewhere
        sortArray.push(fileCopy.isFolder ? 1 : 0);

        return fileCopy;
    });

    if (isRandom) {
        files.sort(() => Math.random() - 0.5);
    } else {
        files.sort((a, b) => {
            // Sort by isFolder first
            const folderDiff = sortArray[files.indexOf(a)] - sortArray[files.indexOf(b)];
            if (folderDiff !== 0) return -folderDiff;

            // Then sort by the secondary array
            const valueA = sortArraySecondary[files.indexOf(a)];
            const valueB = sortArraySecondary[files.indexOf(b)];

            if (valueA < valueB) return -sortDirection;
            if (valueA > valueB) return sortDirection;
            return 0;
        });
    }

    return files;
}

export function isMobile() {
    return window.innerWidth < 768;
}

export function colorBrightness(hex, steps) {
    // Return if not a valid hex color
    if (!/^#([a-f0-9]{3}){1,2}$/i.test(hex)) {
        return hex;
    }

    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    steps = Math.max(-255, Math.min(255, steps));

    // Normalize into a six-character long hex string
    hex = hex.replace('#', '');
    if (hex.length === 3) {
        hex = hex.split('').map(char => char + char).join('');
    }

    // Split into three parts: R, G, and B
    const colorParts = hex.match(/.{2}/g);
    let result = '#';

    colorParts.forEach(color => {
        let decimal = parseInt(color, 16); // Convert to decimal
        decimal = Math.max(0, Math.min(255, decimal + steps)); // Adjust color
        result += decimal.toString(16).padStart(2, '0'); // Convert back to hex and pad
    });

    return result;
}

export function hex2Rgba(color, opacity = false) {
    const defaultColor = 'rgb(0,0,0)';

    // Return default if no color is provided
    if (!color) {
        return defaultColor;
    }

    // Sanitize color if "#" is provided
    if (color[0] === '#') {
        color = color.substring(1);
    }

    // Check if color has 6 or 3 characters and get values
    let hex;
    if (color.length === 6) {
        hex = [color.substring(0, 2), color.substring(2, 4), color.substring(4, 6)];
    } else if (color.length === 3) {
        hex = [color[0] + color[0], color[1] + color[1], color[2] + color[2]];
    } else {
        return defaultColor;
    }

    // Convert hexadec to RGB
    const rgb = hex.map(h => parseInt(h, 16));

    // Check if opacity is set (rgba or rgb)
    if (opacity) {
        if (Math.abs(opacity) > 1) {
            opacity = 1.0;
        }
        return `rgba(${rgb.join(',')},${opacity})`;
    } else {
        return `rgb(${rgb.join(',')})`;
    }
}

export function exportStatistics(start_date, end_date, type = 'all') {
    wp.ajax.post('igd_export_statistics', {
        start_date,
        end_date,
        type,
        nonce: igd.nonce,
    }).done((response) => {
        console.log(response);

        if (response.success) {
            window.location.href = response.url;
        } else {
            Swal.fire({
                title: wp.i18n.__('Error!', 'integrate-google-drive'),
                text: wp.i18n.__('Something went wrong.', 'integrate-google-drive'),
                icon: 'error',
                toast: true,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
            });
        }
    }).fail((error) => {
        console.log(error);

        Swal.fire({
            title: wp.i18n.__('Error!', 'integrate-google-drive'),
            text: wp.i18n.__('Something went wrong.', 'integrate-google-drive'),
            icon: 'error',
            toast: true,
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false,
        });

    });
}

export function formatDateForSQL(dateStr) {
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = ('0' + (date.getMonth() + 1)).slice(-2); // Months are 0-based in JavaScript
    const day = ('0' + date.getDate()).slice(-2);
    return `${year}-${month}-${day}`;
}

export function getEditUrl(file) {
    if (file && file.webViewLink) {
        return file.webViewLink.replace(/\/view$/, '/edit');
    }

    return '';
}

export function extractIdFromShortcode(value) {
    const match = value?.match(/id\s*=\s*(?:"([^"]+)"|'([^']+)'|([^\s\]]+))/);
    return match ? (match[1] || match[2] || match[3]) : null;
}

export function md5(string) {
    function cmn(q, a, b, x, s, t) {
        a = ((a + q + x + t) & 0xFFFFFFFF);
        return (((a << s) | (a >>> (32 - s))) + b) & 0xFFFFFFFF;
    }

    function ff(a, b, c, d, x, s, t) {
        return cmn((b & c) | (~b & d), a, b, x, s, t);
    }

    function gg(a, b, c, d, x, s, t) {
        return cmn((b & d) | (c & ~d), a, b, x, s, t);
    }

    function hh(a, b, c, d, x, s, t) {
        return cmn(b ^ c ^ d, a, b, x, s, t);
    }

    function ii(a, b, c, d, x, s, t) {
        return cmn(c ^ (b | ~d), a, b, x, s, t);
    }

    function md5cycle(x, k) {
        let [a, b, c, d] = x;

        a = ff(a, b, c, d, k[0], 7, -680876936);
        d = ff(d, a, b, c, k[1], 12, -389564586);
        c = ff(c, d, a, b, k[2], 17, 606105819);
        b = ff(b, c, d, a, k[3], 22, -1044525330);
        a = ff(a, b, c, d, k[4], 7, -176418897);
        d = ff(d, a, b, c, k[5], 12, 1200080426);
        c = ff(c, d, a, b, k[6], 17, -1473231341);
        b = ff(b, c, d, a, k[7], 22, -45705983);
        a = ff(a, b, c, d, k[8], 7, 1770035416);
        d = ff(d, a, b, c, k[9], 12, -1958414417);
        c = ff(c, d, a, b, k[10], 17, -42063);
        b = ff(b, c, d, a, k[11], 22, -1990404162);
        a = ff(a, b, c, d, k[12], 7, 1804603682);
        d = ff(d, a, b, c, k[13], 12, -40341101);
        c = ff(c, d, a, b, k[14], 17, -1502002290);
        b = ff(b, c, d, a, k[15], 22, 1236535329);

        a = gg(a, b, c, d, k[1], 5, -165796510);
        d = gg(d, a, b, c, k[6], 9, -1069501632);
        c = gg(c, d, a, b, k[11], 14, 643717713);
        b = gg(b, c, d, a, k[0], 20, -373897302);
        a = gg(a, b, c, d, k[5], 5, -701558691);
        d = gg(d, a, b, c, k[10], 9, 38016083);
        c = gg(c, d, a, b, k[15], 14, -660478335);
        b = gg(b, c, d, a, k[4], 20, -405537848);
        a = gg(a, b, c, d, k[9], 5, 568446438);
        d = gg(d, a, b, c, k[14], 9, -1019803690);
        c = gg(c, d, a, b, k[3], 14, -187363961);
        b = gg(b, c, d, a, k[8], 20, 1163531501);
        a = gg(a, b, c, d, k[13], 5, -1444681467);
        d = gg(d, a, b, c, k[2], 9, -51403784);
        c = gg(c, d, a, b, k[7], 14, 1735328473);
        b = gg(b, c, d, a, k[12], 20, -1926607734);

        a = hh(a, b, c, d, k[5], 4, -378558);
        d = hh(d, a, b, c, k[8], 11, -2022574463);
        c = hh(c, d, a, b, k[11], 16, 1839030562);
        b = hh(b, c, d, a, k[14], 23, -35309556);
        a = hh(a, b, c, d, k[1], 4, -1530992060);
        d = hh(d, a, b, c, k[4], 11, 1272893353);
        c = hh(c, d, a, b, k[7], 16, -155497632);
        b = hh(b, c, d, a, k[10], 23, -1094730640);
        a = hh(a, b, c, d, k[13], 4, 681279174);
        d = hh(d, a, b, c, k[0], 11, -358537222);
        c = hh(c, d, a, b, k[3], 16, -722521979);
        b = hh(b, c, d, a, k[6], 23, 76029189);
        a = hh(a, b, c, d, k[9], 4, -640364487);
        d = hh(d, a, b, c, k[12], 11, -421815835);
        c = hh(c, d, a, b, k[15], 16, 530742520);
        b = hh(b, c, d, a, k[2], 23, -995338651);

        a = ii(a, b, c, d, k[0], 6, -198630844);
        d = ii(d, a, b, c, k[7], 10, 1126891415);
        c = ii(c, d, a, b, k[14], 15, -1416354905);
        b = ii(b, c, d, a, k[5], 21, -57434055);
        a = ii(a, b, c, d, k[12], 6, 1700485571);
        d = ii(d, a, b, c, k[3], 10, -1894986606);
        c = ii(c, d, a, b, k[10], 15, -1051523);
        b = ii(b, c, d, a, k[1], 21, -2054922799);
        a = ii(a, b, c, d, k[8], 6, 1873313359);
        d = ii(d, a, b, c, k[15], 10, -30611744);
        c = ii(c, d, a, b, k[6], 15, -1560198380);
        b = ii(b, c, d, a, k[13], 21, 1309151649);
        a = ii(a, b, c, d, k[4], 6, -145523070);
        d = ii(d, a, b, c, k[11], 10, -1120210379);
        c = ii(c, d, a, b, k[2], 15, 718787259);
        b = ii(b, c, d, a, k[9], 21, -343485551);

        x[0] = (x[0] + a) & 0xFFFFFFFF;
        x[1] = (x[1] + b) & 0xFFFFFFFF;
        x[2] = (x[2] + c) & 0xFFFFFFFF;
        x[3] = (x[3] + d) & 0xFFFFFFFF;
    }

    function md5blk(s) {
        const blocks = [];
        for (let i = 0; i < 64; i += 4) {
            blocks[i >> 2] = s.charCodeAt(i) +
                (s.charCodeAt(i + 1) << 8) +
                (s.charCodeAt(i + 2) << 16) +
                (s.charCodeAt(i + 3) << 24);
        }
        return blocks;
    }

    function md51(s) {
        const n = s.length;
        const state = [1732584193, -271733879, -1732584194, 271733878];
        let i;
        for (i = 64; i <= n; i += 64) {
            md5cycle(state, md5blk(s.substring(i - 64, i)));
        }

        s = s.substring(i - 64);
        const tail = Array(16).fill(0);
        for (i = 0; i < s.length; i++)
            tail[i >> 2] |= s.charCodeAt(i) << ((i % 4) << 3);
        tail[i >> 2] |= 0x80 << ((i % 4) << 3);
        if (i > 55) {
            md5cycle(state, tail);
            tail.fill(0);
        }

        tail[14] = n * 8;
        md5cycle(state, tail);
        return state;
    }

    function rhex(n) {
        const s = '0123456789abcdef';
        let str = '';
        for (let j = 0; j < 4; j++)
            str += s.charAt((n >> (j * 8 + 4)) & 0x0F) + s.charAt((n >> (j * 8)) & 0x0F);
        return str;
    }

    return md51(string).map(rhex).join('');
}

export function handleImport(files) {

    Swal.fire({
        title: wp.i18n.sprintf(wp.i18n._n('Importing %d file', 'Importing %d files', files.length, 'integrate-google-drive'), files.length),
        text: wp.i18n.__('Please wait...', 'integrate-google-drive'),
        html: '<div id="igd-import"></div>',
        showConfirmButton: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        allowOutsideClick: false,
        customClass: {container: 'igd-swal igd-import-swal'},
        showCloseButton: true,
        didOpen: () => {
            const element = document.getElementById('igd-import');

            if (element) {
                ReactDOM.render(<ImportModal files={files}/>, element);
            }
        },
        willClose: () => {
            // trigger import_cancel custom js event
            const event = new CustomEvent('igd_import_cancel');
            document.dispatchEvent(event);
        }
    });
}