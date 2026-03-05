import {useHandleListingResize} from "./functions";

const {useEffect, useRef} = React;

const Modal = ({isOpen, onClose, children, className, target = document.body}) => {

    const modalRef = useRef();

    // Dynamically handle the isList dependency to resize the modal content
    const listClass = useHandleListingResize(modalRef, !isOpen);

    // Close modal when clicking outside
    useEffect(() => {

        const handleOutsideClick = (event) => {
            if (modalRef.current && !modalRef.current?.contains(event.target) && !document.getElementById('igd-pro-modal')?.contains(event.target)) {
                onClose();
            }
        };

        if (isOpen) {
            document.addEventListener('mousedown', handleOutsideClick);
        } else {
            document.removeEventListener('mousedown', handleOutsideClick);
        }

        return () => {
            document.removeEventListener('mousedown', handleOutsideClick);
        }

    }, [isOpen, onClose]);

    if (!isOpen) {
        return null;
    }


    return ReactDOM.createPortal(
        <div className={`igd-modal-overlay ${className ? className : ''} `}>

            <div className={`igd-modal-content ${listClass}`} ref={modalRef}>
                {children}
            </div>

        </div>,

        target
    );
};

export default Modal;
