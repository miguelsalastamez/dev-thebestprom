const {useState, useEffect} = React;

export default function CollapseWrapper({title, description, initOpen, onOpen, onClose, condition, children}) {
    const [isOpen, setIsOpen] = useState(initOpen);

    useEffect(() => {

        if (isOpen) {
            if (onOpen) {
                setTimeout(() => {
                    onOpen();
                }, 100);
            }
        } else {
            if (onClose) {
                setTimeout(() => {
                    onClose();
                }, 100);
            }
        }


    }, [isOpen]);

    // Handle conditional open/close
    useEffect(() => {

        if (typeof condition === 'undefined') return;

        setIsOpen(condition);

    }, [condition]);

    return (
        <div className={`igd-collapse-wrapper ${isOpen ? 'open' : 'closed'}`}>

            <div className="collapse-header" onClick={() => setIsOpen(!isOpen)}>
                <div className="header-title">
                    <h3>{title}</h3>
                    {description && <p>{description}</p>}
                </div>

                <i className={`collapse-toggle dashicons ${isOpen ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'}`}></i>
            </div>

            {isOpen &&
                <div className="collapse-content">
                    {children}
                </div>
            }

        </div>
    )
}