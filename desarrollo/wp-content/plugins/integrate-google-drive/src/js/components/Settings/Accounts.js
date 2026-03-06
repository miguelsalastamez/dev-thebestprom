import {Tooltip} from "react-tooltip";
import {showProModal} from "../../includes/ProModal";
import {
    humanFileSize,
    loadAvatar,
    openAddAccountWindow,
    removeAllLastFolderSessions,
    useMounted
} from "../../includes/functions";

const {
    FormToggle,
    ButtonGroup,
    Button,
} = wp.components;

const {useState, useEffect, useRef} = React;

const {isPro} = igd;

export default function ({data, setData, saveSettings}) {

    const {
        ownApp,
        clientID,
        clientSecret,
    } = data;

    const [accounts, setAccounts] = useState(igd.accounts);
    const [showPrivacy, setShowPrivacy] = useState(false);

    const isDisableMultipleAccounts = !isPro && !!Object.keys(accounts).length;

    const handleSelectFolders = (accountId, accessToken) => {

        function loadPicker() {
            gapi.load('picker', createPicker); // Load only the Picker library
        }

        function createPicker() {

            const picker = new google.picker.PickerBuilder()
                .addView(new google.picker.DocsView(google.picker.ViewId.FOLDERS)
                    .setParent('root')
                    .setSelectFolderEnabled(true)
                )
                .addView(new google.picker.DocsView(google.picker.ViewId.FOLDERS)
                    .setEnableDrives(true)
                    .setSelectFolderEnabled(true)
                )
                .enableFeature(google.picker.Feature.MULTISELECT_ENABLED) // Enable multiple selection
                .setOAuthToken(accessToken)
                .setCallback(pickerCallback)
                .setTitle(wp.i18n.__('Select Specific Folders', 'integrate-google-drive'))
                .build();

            picker.setVisible(true);
        }

        function pickerCallback(data) {
            if (data.action === google.picker.Action.PICKED) {
                const docs = data.docs;

                if (docs.length) {

                    const folders = docs.map(doc => {
                        return {
                            id: doc.id,
                            accountId: accountId,
                            name: doc.name,
                            iconLink: doc.iconUrl,
                            description: doc.description,
                            type: doc.mimeType,
                            parents: [doc.parentId],
                        }
                    });

                    const accountKey = Object.keys(accounts).find(key => accounts[key].id === accountId);

                    // Merge folders
                    const prevFolders = accounts[accountKey].specific_folders || [];

                    const mergedFolders = [...prevFolders, ...folders];
                    const uniqueFolders = mergedFolders.filter((folder, index, self) =>
                            index === self.findIndex((t) => (
                                t.id === folder.id
                            ))
                    );

                    accounts[accountKey].specific_folders = uniqueFolders;
                    setAccounts({...accounts});

                    wp.ajax.post('igd_save_specific_folders', {
                        accountId: accountId,
                        folders: uniqueFolders,
                        nonce: igd.nonce,
                    }).done(() => {
                        Swal.fire({
                            icon: 'success',
                            title: wp.i18n.__('Folders Selected', 'integrate-google-drive'),
                            text: wp.i18n.__('Selected folders have been saved successfully.', 'integrate-google-drive'),
                            confirmButtonText: wp.i18n.__('OK', 'integrate-google-drive'),
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            customClass: {
                                container: 'igd-swal',
                            },
                        });
                    }).fail((error) => {
                        console.error(error);

                        Swal.fire({
                            icon: 'error',
                            title: wp.i18n.__('Error', 'integrate-google-drive'),
                            text: wp.i18n.__('Failed to save selected folders.', 'integrate-google-drive'),
                            confirmButtonText: wp.i18n.__('OK', 'integrate-google-drive'),
                            customClass: {
                                container: 'igd-swal',
                            },
                        });
                    });

                }

            }
        }

        loadPicker();

    }

    // Check if a connection type is changed
    const [isConnectionTypeChanged, setConnectionTypeChanged] = useState(false);

    const prevDataRef = useRef(data);

    const isMounted = useMounted();

    useEffect(() => {

        if (!isMounted) return;

        const prevData = prevDataRef.current;

        if (prevData.ownApp !== ownApp || prevData.clientID !== clientID || prevData.clientSecret !== clientSecret) {
            setConnectionTypeChanged(true);
        } else {
            setConnectionTypeChanged(false);
        }

    }, [ownApp, clientID, clientSecret]);

    useEffect(() => {
        wp.ajax.post('igd_get_storage', {
            nonce: igd.nonce,
            accounts: Object.keys(accounts).map(key => accounts[key].id)
        }).done((usageData) => {

            Object.keys(usageData).forEach(key => {
                accounts[key].storage = usageData[key];

                setAccounts({...accounts});
            });

        }).fail((error) => {
            console.error(error);
        });
    }, []);

    return (
        <div className="igd-settings-body">

            <h3 className={'igd-settings-body-title'}>{wp.i18n.__('Account Connection', 'integrate-google-drive')}</h3>

            <div className="settings-field field-connection-type">

                <h4 className="settings-field-label">{wp.i18n.__("Connection Type", "integrate-google-drive")}</h4>

                <div className="settings-field-content">

                    <ButtonGroup>
                        <Button
                            variant={!ownApp ? "primary" : "secondary"}
                            onClick={() => setData({...data, ownApp: false})}
                            text={wp.i18n.__("Automatic", "integrate-google-drive")}
                            label={wp.i18n.__("Use plugin's default app to connect your account.", "integrate-google-drive")}
                            icon={
                                <svg width="24" height="25" viewBox="0 0 24 25" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <g>
                                        <path
                                            d="M11.0627 24.5002C10.8471 24.4627 10.6314 24.4205 10.4111 24.3877C8.17519 24.0689 6.17363 23.2111 4.42051 21.7861C4.39238 21.7627 4.36426 21.7439 4.3127 21.7111C4.3127 22.0955 4.3127 22.4518 4.3127 22.8408C3.8627 22.8408 3.4127 22.8408 2.9252 22.8408C2.88301 21.6783 2.84082 20.4877 2.79395 19.2736C4.01738 19.2314 5.22207 19.1893 6.45957 19.1471C6.45957 19.6205 6.45957 20.0705 6.45957 20.5674C6.0752 20.5674 5.66738 20.5674 5.20801 20.5674C7.85176 22.9721 12.8111 24.2096 17.2596 21.6924C21.9986 19.0064 24.2252 12.6689 21.2299 7.28301C21.6283 7.05801 22.0268 6.83301 22.4346 6.59863C22.8518 7.31582 23.1752 8.07519 23.4283 8.86269C23.7002 9.71113 23.8736 10.583 23.9533 11.4689C23.958 11.5018 23.9814 11.5299 23.9955 11.5627C23.9955 12.1861 23.9955 12.8143 23.9955 13.4377C23.9814 13.4752 23.9533 13.5127 23.9486 13.5549C23.8455 14.1596 23.7799 14.7783 23.6346 15.3736C22.4627 20.1408 18.5721 23.6518 13.6971 24.3736C13.4111 24.4158 13.1252 24.458 12.8393 24.5049C12.7455 24.5049 12.6518 24.5049 12.558 24.5049C12.483 24.4908 12.4033 24.4768 12.3283 24.4627C12.1221 24.4627 11.9158 24.4627 11.7096 24.4627C11.6018 24.4768 11.4939 24.4908 11.3861 24.5002C11.283 24.5002 11.1705 24.5002 11.0627 24.5002Z"
                                            fill="white"/>
                                        <path
                                            d="M12.8898 0.5C13.1383 0.5375 13.3867 0.575 13.6352 0.6125C15.632 0.898437 17.4508 1.62969 19.082 2.81562C19.1008 2.82969 19.1195 2.83437 19.1477 2.84844C19.1477 2.43125 19.1477 2.02344 19.1477 1.57344C19.5883 1.57344 20.043 1.57344 20.5305 1.57344C20.5727 2.73125 20.6195 3.91719 20.6664 5.14062C19.4523 5.18281 18.2477 5.225 17.0055 5.26719C17.0055 4.80312 17.0055 4.34375 17.0055 3.85625C17.3195 3.85625 17.657 3.85625 18.0227 3.85625C17.4133 3.04531 14.357 1.99531 12.4258 1.91562C10.3773 1.83125 8.46484 2.30937 6.69297 3.33594C1.93984 6.0875 0.0273437 12.0594 2.48359 17.15C2.07109 17.3562 1.65859 17.5578 1.19922 17.7875C0.983594 17.225 0.758594 16.6906 0.571094 16.1375C0.289844 15.3172 0.135156 14.4641 0.0507812 13.6016C0.0460937 13.5641 0.0179687 13.5266 0.00390625 13.4844C0.00390625 12.8281 0.00390625 12.1719 0.00390625 11.5156C0.0367187 11.3141 0.0789062 11.1125 0.107031 10.9109C0.444531 8.5625 1.34922 6.46719 2.91016 4.67187C4.85547 2.44062 7.29297 1.09531 10.2227 0.635937C10.518 0.589062 10.8133 0.546875 11.1086 0.5C11.7039 0.5 12.2945 0.5 12.8898 0.5Z"
                                            fill="white"/>
                                        <path
                                            d="M11.7148 24.4629C11.9211 24.4629 12.1273 24.4629 12.3336 24.4629C12.3289 24.477 12.3289 24.491 12.3289 24.5051C12.1273 24.5051 11.9211 24.5051 11.7195 24.5051C11.7195 24.4863 11.7148 24.4723 11.7148 24.4629Z"
                                            fill="white"/>
                                        <path
                                            d="M11.7141 24.4629C11.7187 24.477 11.7188 24.491 11.7188 24.5004C11.6109 24.5004 11.4984 24.5004 11.3906 24.5004C11.4984 24.4863 11.6062 24.4723 11.7141 24.4629Z"
                                            fill="white"/>
                                        <path
                                            d="M12.3281 24.5002C12.3281 24.4861 12.3281 24.4721 12.3328 24.458C12.4078 24.4721 12.4875 24.4861 12.5625 24.5002C12.4828 24.5002 12.4078 24.5002 12.3281 24.5002Z"
                                            fill="white"/>
                                        <path
                                            d="M4.54297 14.1777C4.54297 12.9684 4.54297 11.7824 4.54297 10.5965C4.54297 10.5637 4.54766 10.5355 4.54766 10.4887C4.95547 10.409 5.35391 10.3199 5.76172 10.2543C5.91172 10.2309 5.96797 10.1465 6.04297 10.0246C6.25859 9.67773 6.17891 9.40117 5.93516 9.11055C5.74766 8.88555 5.60234 8.62305 5.44766 8.39805C6.31953 7.52617 7.18203 6.65898 8.06797 5.77305C8.37734 5.98398 8.71953 6.2043 9.05234 6.44336C9.18828 6.5418 9.29609 6.51836 9.45078 6.48086C9.81172 6.38711 9.93828 6.1668 9.97109 5.82461C9.99453 5.56211 10.0742 5.3043 10.1117 5.0418C10.1305 4.91523 10.1727 4.87305 10.3039 4.87305C11.4289 4.87773 12.5539 4.87773 13.6789 4.87305C13.8008 4.87305 13.857 4.90586 13.8758 5.03711C13.9367 5.40742 14.0258 5.76836 14.0867 6.13867C14.1055 6.26523 14.1758 6.30742 14.2742 6.36367C14.6258 6.58398 14.9211 6.53242 15.2305 6.26055C15.4461 6.07305 15.7039 5.93242 15.9195 5.7918C16.7961 6.66367 17.668 7.53086 18.5539 8.40742C18.3383 8.73086 18.1039 9.07773 17.8648 9.42461C17.7898 9.53242 17.8039 9.6168 17.8367 9.73867C17.9352 10.123 18.1602 10.2918 18.5539 10.3152C18.8539 10.334 19.1492 10.4277 19.4445 10.484C19.4445 11.7215 19.4445 12.9449 19.4445 14.1824C19.032 14.2621 18.6289 14.3465 18.2258 14.4168C18.0852 14.4402 18.0289 14.5105 17.9539 14.6277C17.743 14.9746 17.8039 15.2605 18.0617 15.5605C18.2539 15.7855 18.3945 16.048 18.5492 16.273C17.6773 17.1496 16.8148 18.0215 15.9383 18.9027C15.6148 18.6824 15.268 18.4621 14.9352 18.223C14.8039 18.1293 14.7055 18.148 14.5555 18.1855C14.1758 18.2793 14.0445 18.5184 14.0117 18.8793C13.9883 19.1324 13.9273 19.3855 13.8664 19.634C13.8523 19.6996 13.7586 19.7887 13.7023 19.7887C12.5633 19.798 11.4242 19.798 10.2805 19.7887C10.2289 19.7887 10.1398 19.7043 10.1258 19.648C10.0414 19.2824 9.97109 18.9168 9.90547 18.5465C9.88203 18.4152 9.81641 18.3684 9.70859 18.298C9.35703 18.0824 9.07109 18.1387 8.77109 18.3965C8.55078 18.5887 8.28359 18.734 8.05391 18.884C7.18203 18.0121 6.31484 17.1496 5.42891 16.2684C5.64453 15.9449 5.86953 15.6027 6.10859 15.2699C6.20234 15.1387 6.18359 15.0402 6.14609 14.8902C6.04766 14.534 5.83672 14.384 5.47578 14.3559C5.17109 14.3324 4.86641 14.2434 4.54297 14.1777ZM12.057 8.67461C10.0508 8.66992 8.40547 10.3152 8.39609 12.3215C8.39141 14.3371 10.032 15.9871 12.057 15.9871C14.068 15.9918 15.7039 14.3605 15.7086 12.3449C15.718 10.3199 14.082 8.6793 12.057 8.67461Z"
                                            fill="white"/>
                                        <path
                                            d="M12.052 14.5811C10.8145 14.5764 9.80197 13.5592 9.80666 12.3264C9.81134 11.0936 10.8379 10.0764 12.066 10.0811C13.3129 10.0904 14.3067 11.0936 14.3067 12.3358C14.302 13.5826 13.2942 14.5858 12.052 14.5811Z"
                                            fill="white"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_90_24688">
                                            <rect width="24" height="24" fill="white" transform="translate(0 0.5)"/>
                                        </clipPath>
                                    </defs>
                                </svg>}
                        />

                        <Button
                            variant={ownApp ? "primary" : "secondary"}
                            onClick={() => setData({...data, ownApp: true})}
                            text={wp.i18n.__("Manual", "integrate-google-drive")}
                            label={wp.i18n.__("Use your own Google App to connect your account.", "integrate-google-drive")}
                            icon={<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25"
                                       fill="none">
                                <g clipPath="url(#clip0_90_25221)">
                                    <path
                                        d="M0 20.2434C0.0526316 20.1323 0.0994152 20.0212 0.169591 19.9159C0.403509 19.5592 0.754386 19.4539 1.15789 19.4539C5.22807 19.4539 9.29825 19.4539 13.3684 19.4598C13.5906 19.4598 13.6725 19.3896 13.7485 19.1966C14.5906 17.027 17.2222 16.2726 19.0643 17.6703C19.6023 18.0797 20 18.6001 20.2164 19.2493C20.269 19.413 20.3509 19.4598 20.5205 19.4539C21.2982 19.4422 22.0819 19.4481 22.8596 19.4481C23.2573 19.4481 23.5965 19.5767 23.8129 19.9335C24.2222 20.6001 23.7485 21.4364 22.9474 21.4481C22.1696 21.4598 21.386 21.4598 20.6082 21.4422C20.3801 21.4364 20.2749 21.5007 20.193 21.7229C19.5439 23.4247 17.7018 24.3253 15.9649 23.7873C14.9181 23.4598 14.1813 22.7697 13.7602 21.7522C13.7193 21.6586 13.6842 21.565 13.6433 21.4481C13.5614 21.4481 13.4678 21.4481 13.3743 21.4481C9.37427 21.4481 5.37427 21.4422 1.37427 21.4598C0.74269 21.4598 0.263158 21.3136 0.00584795 20.6995C0 20.5533 0 20.4013 0 20.2434ZM15.4737 20.4481C15.4678 21.2726 16.1462 21.9569 16.9649 21.9569C17.7719 21.9569 18.4444 21.296 18.462 20.4715C18.4795 19.6645 17.7895 18.9686 16.9649 18.9686C16.1462 18.9686 15.4854 19.6294 15.4737 20.4481Z"
                                        fill="#2FB44B"/>
                                    <path
                                        d="M0 4.24302C0.0233918 4.20793 0.0526316 4.17284 0.0701754 4.13191C0.25731 3.71085 0.584795 3.50033 1.04094 3.49448C1.70175 3.48863 2.36842 3.49448 3.02924 3.49448C6.47368 3.49448 9.91813 3.49448 13.3684 3.49448C13.4561 3.49448 13.538 3.49448 13.6257 3.49448C13.6082 3.48278 13.5965 3.46524 13.5789 3.45354C13.848 3.03249 14.076 2.57635 14.386 2.19038C15.3977 0.927227 17.386 0.634829 18.7485 1.50033C19.4444 1.94477 19.9532 2.52957 20.2222 3.30734C20.2749 3.45939 20.3567 3.50033 20.5088 3.49448C21.3216 3.48863 22.1287 3.48863 22.9415 3.49448C23.4094 3.50033 23.7778 3.77518 23.9064 4.20208C24.0351 4.62313 23.8889 5.08512 23.5146 5.3015C23.3158 5.41261 23.0643 5.47693 22.8304 5.48278C22.0643 5.50617 21.3041 5.50033 20.538 5.48863C20.3509 5.48278 20.269 5.54126 20.2047 5.7167C19.6725 7.12606 18.3626 8.0091 16.883 7.97986C15.5322 7.95647 14.2398 7.05588 13.7661 5.79272C13.6784 5.55296 13.5731 5.48863 13.3216 5.48863C9.31579 5.49448 5.30994 5.48863 1.29825 5.50033C0.672515 5.50617 0.222222 5.31904 0 4.71085C0 4.55296 0 4.40091 0 4.24302ZM16.9708 5.98571C17.7895 5.97986 18.4503 5.31904 18.462 4.50033C18.4737 3.69331 17.7719 2.99155 16.9591 2.9974C16.1345 3.00325 15.462 3.68746 15.4737 4.51202C15.4854 5.33074 16.152 5.98571 16.9708 5.98571Z"
                                        fill="#2FB44B"/>
                                    <path
                                        d="M-1.14217e-05 12.2431C0.0643161 12.1261 0.122796 11.9975 0.198819 11.8863C0.415193 11.5939 0.713439 11.477 1.07016 11.4828C1.84209 11.4887 2.61402 11.477 3.38595 11.4887C3.59063 11.4945 3.67835 11.4302 3.74853 11.2372C4.40935 9.50038 6.26899 8.5881 8.02338 9.15535C9.08771 9.50038 9.8187 10.2138 10.2222 11.2548C10.2865 11.4244 10.3626 11.4887 10.5497 11.4887C14.6374 11.4828 18.7193 11.4828 22.807 11.4828C23.4152 11.4828 23.8012 11.7577 23.924 12.2665C24.0526 12.8279 23.6374 13.4068 23.0643 13.4594C22.9474 13.4711 22.8304 13.4711 22.7134 13.4711C18.6842 13.4711 14.6491 13.477 10.6199 13.4653C10.3743 13.4653 10.2748 13.5472 10.193 13.7635C9.538 15.4711 7.64911 16.3776 5.91227 15.7928C4.87133 15.4478 4.14619 14.746 3.74853 13.7168C3.6725 13.5238 3.59063 13.4594 3.38595 13.4653C2.70174 13.4828 2.01168 13.4478 1.32747 13.477C0.701743 13.5062 0.233907 13.3191 -0.00585938 12.7109C-1.14217e-05 12.553 -1.14217e-05 12.401 -1.14217e-05 12.2431ZM8.47952 12.4711C8.47952 11.6524 7.81285 10.9858 6.99414 10.9799C6.17543 10.9741 5.48537 11.6641 5.49122 12.4828C5.49706 13.2957 6.16958 13.9682 6.98244 13.9741C7.80116 13.9799 8.47952 13.3016 8.47952 12.4711Z"
                                        fill="#2FB44B"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_90_25221">
                                        <rect width="24" height="24" fill="white" transform="translate(0 0.5)"/>
                                    </clipPath>
                                </defs>
                            </svg>}
                        />
                    </ButtonGroup>

                    <p className={'description'}>{wp.i18n.__("Choose how you want to connect your Google accounts with the plugin.", "integrate-google-drive")}</p>

                    <div className="igd-notice igd-notice-info loading-method-info">
                        <div className="igd-notice-content">
                            <p>
                                <code>{wp.i18n.__("Automatic", "integrate-google-drive")}</code> → {wp.i18n.__("Sign-in with your Google account using the plugin's default Google App. No configuration needed.", "integrate-google-drive")}
                            </p>
                            <p>
                                <code>{wp.i18n.__("Manual", "integrate-google-drive")}</code> → {wp.i18n.__(`Create your own Google app and use it to connect your Google account with the plugin.`, "integrate-google-drive")}
                            </p>
                        </div>
                    </div>

                    {!!ownApp &&
                        <div className="settings-field-sub">
                            <div className="settings-field field-own-app">

                                <div className="igd-notice igd-notice-warning own-google-app-warning">
                                    <p className={`igd-notice-content`}>
                                        {wp.i18n.__('Using your own Google App is an optional option. For an easy setup you can just use the default App of the plugin itself. The advantage of using your own app is limited. If you decided to create your own Google App anyway, please enter your app Client ID & Secret key in the below settings.', 'integrate-google-drive')}
                                    </p>
                                    <br/>
                                    <p className={`igd-notice-content`}>
                                        On the <a target={"_blank"}
                                                  href={"https://softlabbd.com/docs/how-to-link-your-own-google-app-with-the-plugin/"}>Documentation</a> page
                                        you can find how you can create your own Google App.
                                    </p>
                                </div>

                                {/* App Client ID */}
                                <h4 className="settings-field-label">{wp.i18n.__('App Client ID', 'integrate-google-drive')}</h4>
                                <div className="settings-field-content">
                                    <input type="text" value={clientID}
                                           onChange={e => setData({...data, clientID: e.target.value})}
                                           placeholder={wp.i18n.__('App Client ID', 'integrate-google-drive')}
                                    />
                                    <p className="description">{wp.i18n.__('Insert you app client ID.', 'integrate-google-drive')}</p>
                                </div>

                                {/* App Secret Key */}
                                <h4 className="settings-field-label">{wp.i18n.__('App Secret Key', 'integrate-google-drive')}</h4>
                                <div className="settings-field-content">
                                    <input type="text" value={clientSecret}
                                           onChange={e => setData({...data, clientSecret: e.target.value})}
                                           placeholder={wp.i18n.__('App Secret Key', 'integrate-google-drive')}
                                    />
                                    <p className="description">{wp.i18n.__('Insert you app secret key.', 'integrate-google-drive')}</p>
                                </div>

                                {/* Redirect URI */}
                                <h4 className="settings-field-label">{wp.i18n.__('Redirect URI', 'integrate-google-drive')}</h4>
                                <div className="settings-field-content">

                                    <input type="text"
                                           value={`${igd.adminUrl}?action=integrate-google-drive-authorization`}
                                           readOnly
                                           onClick={e => {
                                               e.target.select();
                                               e.target.setSelectionRange(0, 99999);

                                               navigator.clipboard.writeText(e.target.value);

                                               Swal.fire({
                                                   title: wp.i18n.__('Copied to clipboard.', 'integrate-google-drive'),
                                                   icon: 'success',
                                                   timer: 1500,
                                                   toast: true,
                                                   position: 'top-end',
                                                   showConfirmButton: false,
                                                   timerProgressBar: true,
                                               });
                                           }}
                                    />

                                    <p className="description">{wp.i18n.__('Copy the above redirect URI and set to your Google app.', 'integrate-google-drive')}</p>
                                </div>

                            </div>
                        </div>
                    }

                </div>
            </div>

            <h3 className={'igd-settings-title'}>{wp.i18n.__('Accounts', 'integrate-google-drive')}</h3>

            {
                Object.keys(accounts).length ?
                    Object.keys(accounts).map(key => {

                        const {
                            id,
                            name,
                            photo,
                            email,
                            lost,
                            is_lost,
                            is_specific_folders,
                            specific_folders = [],
                            storage,
                        } = accounts[key];

                        const accountId = id;

                        const usagePercentage = Math.round((storage.usage * 100) / storage.limit);

                        return (
                            <div key={key} className="igd-account-item">

                                <img
                                    referrerPolicy={`no-referrer`}
                                    onError={({currentTarget}) => loadAvatar(currentTarget, email)}
                                    src={photo}
                                />

                                <div className="igd-account-item-info">
                                    <span className={`account-name`}>{name}</span>
                                    <span className={`account-email`}>{email}</span>

                                    <div className="storage-info-wrap">

                                        <div className="storage-info">

                                            <div className="storage-info-sidebar">
                                                <div style={{width: `${usagePercentage}%`}}
                                                     className={`storage-info-fill ${usagePercentage > 90 ? 'fill-danger' : ''}`}></div>
                                            </div>

                                            <span>{humanFileSize(storage.usage)} of {humanFileSize(storage.limit)} used</span>
                                        </div>

                                    </div>

                                </div>

                                <div className="igd-account-item-action">

                                    {(lost || is_lost) &&
                                        <Button
                                            className={`igd-btn btn-info`}
                                            onClick={() => openAddAccountWindow()}
                                            icon={`update`}
                                            text={wp.i18n.__("Refresh", "integrate-google-drive")}
                                            label={wp.i18n.__("Refresh account connection.", "integrate-google-drive")}
                                        />
                                    }

                                    <Button
                                        className={`igd-btn btn-danger`}
                                        onClick={() => {

                                            Swal.fire({
                                                title: wp.i18n.__("Are you sure?", "integrate-google-drive"),
                                                text: wp.i18n.__("You won't be able to revert this!", "integrate-google-drive"),
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonText: wp.i18n.__("Yes, remove it!", "integrate-google-drive"),
                                                reverseButtons: true,
                                                showLoaderOnConfirm: true,
                                                customClass: {container: 'igd-swal igd-swal-reverse'},
                                                preConfirm: () => {
                                                    return wp.ajax.post('igd_delete_account', {
                                                        id,
                                                        nonce: igd.nonce,
                                                    }).fail(() => {
                                                        Swal.showValidationMessage(wp.i18n.__("Server error. Please try again.", "integrate-google-drive"));
                                                        Swal.hideLoading();
                                                    });
                                                }
                                            }).then(result => {
                                                if (result.value) {
                                                    delete accounts[key];
                                                    setAccounts({...accounts});

                                                    // update the window.igd.accounts
                                                    window.igd.accounts = accounts;

                                                    Swal.fire({
                                                        title: wp.i18n.__("Removed!", "integrate-google-drive"),
                                                        text: wp.i18n.__("Account has been removed.", "integrate-google-drive"),
                                                        icon: 'success',
                                                        showConfirmButton: false,
                                                        timer: 1500,
                                                        toast: true,
                                                    });

                                                }
                                            });

                                        }}
                                        icon={`trash`}
                                        text={wp.i18n.__("Remove", "integrate-google-drive")}
                                        label={wp.i18n.__("Remove this Google account from the plugin.", "integrate-google-drive")}
                                    />

                                </div>

                                {/* Specific Folders */}
                                <div className="specific-folders-section">

                                    <div className="specific-folders-title-wrap">
                                        <span
                                            className="specific-folders-title">{wp.i18n.__('Specific Folders', 'integrate-google-drive')}</span>

                                        <FormToggle
                                            data-tooltip-content="PRO Feature"
                                            data-tooltip-id={'specific-folders-pro'}
                                            checked={isPro && is_specific_folders}
                                            className={!isPro ? 'disabled' : ''}
                                            onChange={() => {

                                                if (!isPro) {
                                                    showProModal(wp.i18n.__('Upgrade to PRO to allow specific folders in the plugin.', 'integrate-google-drive'));
                                                    return;
                                                }

                                                removeAllLastFolderSessions();

                                                accounts[key].is_specific_folders = !is_specific_folders;
                                                setAccounts({...accounts});

                                                wp.ajax.post('igd_toggle_specific_folders', {
                                                    account_id: id,
                                                    value: is_specific_folders ? 0 : 1,
                                                    nonce: igd.nonce,
                                                });

                                            }}
                                        />

                                        {!isPro &&
                                            <Tooltip
                                                id="specific-folders-pro"
                                                effect="solid"
                                                place="right"
                                                variant={'warning'}
                                                className="igd-tooltip"
                                            />
                                        }

                                        <p className={'specific-folders-desc'}>
                                            {wp.i18n.__('Allow only specific folders to be used in the plugin.', 'integrate-google-drive')}

                                            <a href="https://softlabbd.com/docs/how-to-restrict-access-to-specific-folders-in-the-plugin/"
                                               target="_blank">
                                                {wp.i18n.__('Learn More', 'integrate-google-drive')}
                                            </a>
                                        </p>
                                    </div>

                                    {(isPro && is_specific_folders) &&
                                        <div className={`specific-folders-wrap`}>

                                            <div className="template-folder">
                                                {
                                                    specific_folders.map((folder, index) => {
                                                        let {id, name, iconLink} = folder;

                                                        iconLink = iconLink || `${igd.pluginUrl}/assets/images/icons/folder.png`;

                                                        return (
                                                            <div key={index} className="template-folder-item">

                                                                <span className="folder-index">{index + 1}. </span>

                                                                <img src={iconLink}/>

                                                                <span className="template-folder-name">{name}</span>

                                                                <div className="dashicons dashicons-no-alt"
                                                                     onClick={() => {
                                                                         const newFolders = specific_folders.filter(folder => folder.id !== id);

                                                                         accounts[key].specific_folders = newFolders;
                                                                         setAccounts({...accounts});

                                                                         removeAllLastFolderSessions();


                                                                         wp.ajax.post('igd_remove_specific_folders', {
                                                                             account_id: accountId,
                                                                             id: id,
                                                                             nonce: igd.nonce,
                                                                         }).done(() => {
                                                                                 Swal.fire({
                                                                                     title: wp.i18n.__("Removed!", "integrate-google-drive"),
                                                                                     text: wp.i18n.__("Folder has been removed from the specific folders.", "integrate-google-drive"),
                                                                                     icon: 'success',
                                                                                     showConfirmButton: false,
                                                                                     timer: 1500,
                                                                                     toast: true,
                                                                                 });
                                                                             }
                                                                         ).fail(() => {
                                                                             Swal.fire({
                                                                                 title: wp.i18n.__("Error!", "integrate-google-drive"),
                                                                                 text: wp.i18n.__("Server error. Please try again.", "integrate-google-drive"),
                                                                                 icon: 'error',
                                                                                 showConfirmButton: false,
                                                                                 timer: 1500,
                                                                                 toast: true,
                                                                             });
                                                                         });

                                                                     }}
                                                                ></div>
                                                            </div>
                                                        )
                                                    })
                                                }
                                            </div>

                                            <Button
                                                data-tooltip-content="PRO Feature"
                                                data-tooltip-id={'specific-folders'}
                                                className="igd-btn btn-success"
                                                onClick={() => {
                                                    wp.ajax.post('igd_get_access_token', {
                                                        id,
                                                        nonce: igd.nonce,
                                                    }).done((accessToken) => {
                                                        handleSelectFolders(id, accessToken);
                                                    });
                                                }}
                                                icon={`open-folder`}
                                                text={wp.i18n.__('Select Folders', 'integrate-google-drive')}
                                                label={wp.i18n.__('Select specific folders to use in the plugin.', 'integrate-google-drive')}
                                            />
                                        </div>
                                    }

                                </div>

                            </div>
                        )
                    })
                    :
                    <div className="no-account-placeholder">
                        <span
                            className="placeholder-heading">{wp.i18n.__("You didn't link any Google account.", "integrate-google-drive")}</span>
                        <span
                            className="placeholder-desc">{wp.i18n.__("Link a Google account to continue.", "integrate-google-drive")}</span>
                    </div>
            }

            <Button
                data-tooltip-content={wp.i18n.__("Multiple Accounts - PRO", "integrate-google-drive")}
                data-tooltip-id="addAccountPromo"
                className={`igd-btn add-account-btn ${isDisableMultipleAccounts ? 'disabled' : ''}`}
                onClick={() => {
                    if (isDisableMultipleAccounts) {
                        showProModal(wp.i18n.__('Upgrade to PRO to add multiple accounts.', 'integrate-google-drive'));
                        return;
                    }

                    if (isConnectionTypeChanged) {
                        saveSettings(data)
                            .then(() => {
                                window.location.reload();
                            });

                        return;
                    }

                    // if is ownApp and clientID or clientSecret is empty
                    if (ownApp && (!clientID || !clientSecret)) {
                        Swal.fire({
                            title: wp.i18n.__("App Client ID & Secret Key is required.", "integrate-google-drive"),
                            text: wp.i18n.__("Please enter your app client ID & secret key.", 'integrate-google-drive'),
                            icon: 'warning',
                            showCancelButton: false,
                            confirmButtonText: wp.i18n.__("OK", "integrate-google-drive"),
                            customClass: {container: 'igd-swal'}
                        });

                        return;
                    }

                    openAddAccountWindow();
                }}
                icon={<img src={`${igd.pluginUrl}/assets/images/google-icon.png`}/>}
                text={isConnectionTypeChanged ? wp.i18n.__('Save Changes', 'integrate-google-drive') : wp.i18n.__('Add Account', 'integrate-google-drive')}
                label={isConnectionTypeChanged ? wp.i18n.__('Save changes and reload the page.', 'integrate-google-drive') : wp.i18n.__('Add new Google account', 'integrate-google-drive')}
            />

            {!!isDisableMultipleAccounts &&
                <Tooltip
                    id="addAccountPromo"
                    effect="solid"
                    place="right"
                    variant={'warning'}
                    className="igd-tooltip"
                />
            }

            <div className="privacy-text-wrap">

                <div className="privacy-text-btn" onClick={() => setShowPrivacy(!showPrivacy)}>

                    <img src={`${igd.pluginUrl}/assets/images/settings/privacy.svg`} alt="Privacy"/>

                    <span>{wp.i18n.__("See what happens with your data when you authorize?", "integrate-google-drive")}</span>
                    <i className="dashicons dashicons-arrow-down-alt2"></i>
                </div>

                {showPrivacy &&
                    <div className="privacy-text">
                        <h4>{wp.i18n.__('Requested scopes and justification', 'integrate-google-drive')}</h4>

                        <p
                            dangerouslySetInnerHTML={{
                                __html: wp.i18n.sprintf(
                                    wp.i18n.__(`In order to display your Google Drive cloud files, you have to authorize it with your Google account. The authorization will ask you to grant the application the %s scope. The scope is needed to allow the plugin to see, edit, create, and delete all of your Google Drive files and files that are shared with you.`, 'integrate-google-drive'),
                                    '<code>https://www.googleapis.com/auth/drive</code>'
                                ),
                            }}
                        ></p>


                        <h4>{wp.i18n.__('Information about the data', 'integrate-google-drive')}</h4>
                        <p>
                            {wp.i18n.__(`The authorization tokens will be stored, encrypted, on your server and is not accessible by any third party.
                            When you use the Application, all communications are strictly between your server and the
                            cloud storage service servers. We do not collect and do not have access to your personal
                            data.`, 'integrate-google-drive')}
                        </p>
                    </div>
                }
            </div>

        </div>
    )
}