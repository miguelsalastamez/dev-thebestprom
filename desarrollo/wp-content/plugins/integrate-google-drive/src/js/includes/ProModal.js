const {useEffect, useState} = React;

export default function ProModal({text, isDismissable = true}) {
    const [isOpen, setIsOpen] = useState(true);

    useEffect(() => {
        if (!isOpen) {
            ReactDOM.unmountComponentAtNode(document.getElementById('igd-pro-modal'));
        }
    }, [isOpen]);

    useEffect(() => {
        const $ = jQuery;

        // On click outside modal close modal using jQuery
        if (isDismissable) {
            $(document).on('click', '.igd-pro-modal-wrap', function (e) {
                if ($(e.target).hasClass('igd-pro-modal-wrap')) {
                    setIsOpen(false);
                }
            });
        }

        // Timer
        function updateTimer() {
            const now = new Date().getTime();
            let distance = targetTime - now;

            // If the count down is over, reset it
            if (distance < 0) {
                setNewTargetTime();
                distance = targetTime - now;
            }

            // Time calculations for days, hours, minutes and seconds
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Display the result in the respective elements
            $('.timer .days span:first').text(days);
            $('.timer .hours span:first').text(hours);
            $('.timer .minutes span:first').text(minutes);
            $('.timer .seconds span:first').text(seconds);
        }

        // Function to set a new target time
        function setNewTargetTime() {
            const newTargetTime = new Date().getTime() + 2.3 * 24 * 60 * 60 * 1000; // 2 days from now
            localStorage.setItem('igd_offer_time', newTargetTime);
            targetTime = newTargetTime;
        }

        // Get or set the target time
        let targetTime = localStorage.getItem('igd_offer_time');
        if (!targetTime || isNaN(targetTime)) {
            setNewTargetTime();
        } else {
            targetTime = parseInt(targetTime);
        }

        // Update the timer every second
        setInterval(updateTimer, 1000);

    }, []);

    return (
        isOpen ?
            <div className="igd-pro-modal-wrap">
                <div className="igd-pro-modal">
                    <span className="igd-pro-modal-close" onClick={() => setIsOpen(false)}>&times;</span>

                    <img src={`${igd.pluginUrl}/assets/images/offer.svg`} alt="Upgrade to Pro"/>

                    <h3>{wp.i18n.__('Unlock PRO Features', 'integrate-google-drive')}</h3>
                    <p>{text}</p>

                    <div className="discount">
                        <span className="discount-special">{wp.i18n.__('Special', 'igd-dark-mode')}</span>
                        <span className="discount-text">{wp.i18n.__('30% OFF', 'igd-dark-mode')}</span>
                    </div>

                    <div className="timer">
                        <div className="days">
                            <span>0</span>
                            <span>DAYS</span>
                        </div>
                        <div className="hours">
                            <span>0</span>
                            <span>HOURS</span>
                        </div>
                        <div className="minutes">
                            <span>0</span>
                            <span>MINUTES</span>
                        </div>
                        <div className="seconds">
                            <span>0</span>
                            <span>SECONDS</span>
                        </div>
                    </div>

                    <div className="igd-pro-modal-actions">
                        <a href={`https://softlabbd.com/integrate-google-drive-pricing`}
                           className="igd-btn btn-primary"
                           target={"_blank"}
                        >
                            {wp.i18n.__('Claim Discount', 'integrate-google-drive')}
                        </a>
                    </div>

                </div>
            </div>
            : null
    );
}

export function showProModal(text = wp.i18n.__('Upgrade to PRO to use this feature.', 'integrate-google-drive')) {
    let element = document.getElementById('igd-pro-modal');

    if (!element) {
        element = document.createElement('div');
        element.id = 'igd-pro-modal';
        document.body.appendChild(element);
    }

    ReactDOM.render(<ProModal text={text}/>, element);
}