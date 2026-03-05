import {
    base64Encode,
    getThumb,
    isAudioVideoType,
    isFolder,
    isImageType,
} from "../../includes/functions";

const {
    ButtonGroup,
    Button,
} = wp.components || {};

const {useState, useEffect} = React;


export default function ShareModal({file}) {

    const {channels = ['shareLink', 'embedCode', 'email',],} = igd.settings;

    const {id, name, accountId} = file;

    function copyLink(e) {
        const element = jQuery(e.target).parents('#igd-share-modal').find('.share-link input');
        const copyText = element.val();  // Grabbing the value directly from the input

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(copyText)
                .then(() => {
                    showSwalMessage();
                })
                .catch(() => {
                    fallbackCopy(copyText);
                    showSwalMessage();
                });
        } else {
            fallbackCopy(copyText);
            showSwalMessage();
        }

        function fallbackCopy(textToCopy) {
            const textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand("Copy");
            textArea.remove();
        }

        function showSwalMessage() {
            Swal.fire({
                title: wp.i18n.__('Copied', "integrate-google-drive"),
                text: wp.i18n.__('Link copied to clipboard', "integrate-google-drive"),
                icon: 'success',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                toast: true,
            });
        }
    }

    const [linkType, setLinkType] = useState('preview');

    const fileString = base64Encode(JSON.stringify({id, accountId}));

    const [link, setLink] = useState(`${igd.homeUrl}/?direct_file=${fileString}`);

    const copyIframe = () => {
        const input = document.querySelector('.share-link textarea');
        input.select();
        document.execCommand('copy');

        setTimeout(() => {
            Swal.fire({
                title: wp.i18n.__('Copied!', 'integrate-google-drive'),
                text: wp.i18n.__('Embed code copied to clipboard.', 'integrate-google-drive'),
                icon: 'success',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                toast: true,
            });
        }, 500);
    }

    const [shareType, setShareType] = useState('link');

    useEffect(() => {


        if ('download' === linkType) {

            const fileIdsParam = isFolder(file) ? `file_ids=${base64Encode(JSON.stringify([id]))}` : `id=${id}&accountId=${accountId}`;

            let downloadLink = `${igd.homeUrl}?igd_download=1&${fileIdsParam}`;

            if (isAudioVideoType(file)) {
                downloadLink = `${igd.homeUrl}/?igd_stream=1&id=${id}&account_id=${accountId}&ignore_limit=1`;
            } else if (isImageType(file)) {
                downloadLink = getThumb(file, 'full');
            }

            setLink(downloadLink);
        } else {
            setLink(`${igd.homeUrl}/?direct_file=${fileString}`);
        }


    }, [linkType]);


    return (
        link &&
        <>

            {!!ButtonGroup && !!Button &&
                <div className="link-types-wrap">
                    <h4>{wp.i18n.__('Link Type', 'integrate-google-drive')}</h4>

                    <ButtonGroup>
                        <Button
                            variant={linkType === 'preview' ? 'primary' : 'secondary'}
                            size={'default'}
                            onClick={() => setLinkType('preview')}
                        >
                            <i className="dashicons dashicons-visibility"></i>
                            {wp.i18n.__('Preview Link', 'integrate-google-drive')}</Button>
                        <Button
                            variant={linkType === 'download' ? 'primary' : 'secondary'}
                            size={'default'}
                            onClick={() => setLinkType('download')}
                        >
                            <i className="dashicons dashicons-download"></i>
                            {wp.i18n.__('Download Link', 'integrate-google-drive')}</Button>
                    </ButtonGroup>

                    <p className="description">{
                        'preview' === linkType ?
                            wp.i18n.__('Choose the type of link you want to share.', 'integrate-google-drive')
                            :
                            wp.i18n.__('Choose the type of link you want to generate.', 'integrate-google-drive')
                    }</p>

                </div>
            }


            <div className="share-link">
                {shareType === 'link' ?
                    <input type="text" value={link} readOnly onClick={copyLink}/>
                    :
                    <textarea readOnly onClick={copyIframe}>
                    {`<iframe src="${link}&embed=1" width="100%" height="480" frameBorder="0" allowTransparency="true" allow="encrypted-media" frameborder="0" allowfullscreen="allowfullscreen" mozallowfullscreen="true" webkitallowfullscreen="true"></iframe>`}
                </textarea>
                }
            </div>

            <div className="share-links">

                {
                    'download' === linkType ?

                        <div className="share-item link" onClick={(e) => {
                            setShareType('link');
                            copyLink(e);
                        }}>
                            <i className="dashicons dashicons-admin-links"></i>
                            <span>{wp.i18n.__('Copy Link', 'integrate-google-drive')}</span>
                        </div>

                        :

                        <>
                            {channels.includes('shareLink') &&
                                <div className="share-item link" onClick={(e) => {
                                    setShareType('link');
                                    copyLink(e);
                                }}>
                                    <i className="dashicons dashicons-admin-links"></i>
                                    <span>{wp.i18n.__('Copy Link', 'integrate-google-drive')}</span>
                                </div>
                            }

                            {channels.includes('embedCode') &&
                                <div className="share-item embed" onClick={() => setShareType('embed')}>
                                    <i className="dashicons dashicons-editor-code"></i>
                                    <span>{wp.i18n.__('Embed', 'integrate-google-drive')}</span>
                                </div>
                            }

                            {channels.includes('email') &&
                                <a className="share-item email" href={`mailto:?subject=${name}&body=${link}`}
                                   onClick={() => setShareType('link')}>
                                    <i className="dashicons dashicons-email"></i>
                                    <span>{wp.i18n.__('Email', 'integrate-google-drive')}</span>
                                </a>
                            }

                            {channels.includes('facebook') &&
                                <a className="share-item facebook"
                                   href={`https://www.facebook.com/sharer/sharer.php?u=${link}`}
                                   target="_blank" onClick={() => setShareType('link')}>
                                    <i className="dashicons dashicons-facebook"></i>
                                    <span>{wp.i18n.__('Facebook', 'integrate-google-drive')}</span>
                                </a>
                            }

                            {channels.includes('twitter') &&
                                <a className="share-item twitter"
                                   href={`https://twitter.com/intent/tweet?text=${name}&url=${link}`} target="_blank"
                                   onClick={() => setShareType('link')}>
                                    <i className="dashicons dashicons-twitter"></i>
                                    <span>{wp.i18n.__('Twitter', 'integrate-google-drive')}</span>
                                </a>
                            }

                            {channels.includes('whatsapp') &&
                                <a className="share-item whatsapp" href={`https://wa.me/?text=${link}`} target="_blank"
                                   onClick={() => setShareType('link')}>
                                    <i className="dashicons dashicons-whatsapp"></i>
                                    <span>{wp.i18n.__('WhatsApp', 'integrate-google-drive')}</span>
                                </a>
                            }
                        </>

                }


            </div>
        </>
    )
}

export function handleShare(activeFile, isDirectLink = false) {
    const title = isDirectLink ? wp.i18n.__('Direct Link', 'integrate-google-drive') : wp.i18n.__('Share', 'integrate-google-drive');

    Swal.fire({
        title,
        html: `<div id="igd-share-modal"></div>`,
        didOpen: () => {
            const element = document.getElementById('igd-share-modal');

            ReactDOM.render(<ShareModal file={activeFile}/>, element);
        },
        showCloseButton: true,
        showConfirmButton: false,
        showCancelButton: false,
        customClass: {
            container: 'igd-swal share-modal',
        },
    })
}