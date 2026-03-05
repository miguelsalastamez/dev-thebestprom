import {usePagination, DOTS} from './usePagination';

const Pagination = props => {
    const {
        onPageChange,
        pageCount,
        siblingCount = 1,
        currentPage = 1,
        className
    } = props;

    const paginationRange = usePagination({
        pageCount,
        siblingCount,
        currentPage,
    });


    if (currentPage === 0 || paginationRange?.length < 2) {
        return null;
    }

    const onNext = () => {
        onPageChange(currentPage + 1);
    };

    const onPrevious = () => {
        onPageChange(currentPage - 1);
    };

    let lastPage = paginationRange[paginationRange?.length - 1];
    return (
        <ul
            className={`pagination-container ${className ? className : ''}`}
        >
            <li
                className={`pagination-item ${currentPage === 1 ? 'disabled' : ''}`}
                onClick={() => currentPage !== 1 && onPrevious()}
            >
                <svg height="15px" width="15px" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                     viewBox="0 0 490.787 490.787">
                    <pat
                        d="M362.671,490.787c-2.831,0.005-5.548-1.115-7.552-3.115L120.452,253.006c-4.164-4.165-4.164-10.917,0-15.083L355.119,3.256c4.093-4.237,10.845-4.354,15.083-0.262c4.237,4.093,4.354,10.845,0.262,15.083c-0.086,0.089-0.173,0.176-0.262,0.262L143.087,245.454l227.136,227.115c4.171,4.16,4.179,10.914,0.019,15.085C368.236,489.664,365.511,490.792,362.671,490.787z"/>
                    <path
                        d="M362.671,490.787c-2.831,0.005-5.548-1.115-7.552-3.115L120.452,253.006c-4.164-4.165-4.164-10.917,0-15.083L355.119,3.256c4.093-4.237,10.845-4.354,15.083-0.262c4.237,4.093,4.354,10.845,0.262,15.083c-0.086,0.089-0.173,0.176-0.262,0.262L143.087,245.454l227.136,227.115c4.171,4.16,4.179,10.914,0.019,15.085C368.236,489.664,365.511,490.792,362.671,490.787z"/>
                </svg>
            </li>
            {paginationRange.map(pageNumber => {

                if (pageNumber === DOTS) {
                    return <li key={pageNumber} className="pagination-item dots">&#8230;</li>;
                }

                return (
                    <li
                        key={pageNumber}
                        className={`pagination-item ${pageNumber === currentPage ? 'selected' : ''}`}

                        onClick={() => onPageChange(pageNumber)}
                    >
                        {pageNumber}
                    </li>
                );
            })}
            <li
                className={`pagination-item ${currentPage === lastPage ? 'disabled' : ''}`}
                onClick={() => currentPage !== lastPage && onNext()}
            >
                <svg height="15px" width="15px" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                     viewBox="0 0 490.8 490.8">
                    <path
                        d="M135.685,3.128c-4.237-4.093-10.99-3.975-15.083,0.262c-3.992,4.134-3.992,10.687,0,14.82l227.115,227.136L120.581,472.461c-4.237,4.093-4.354,10.845-0.262,15.083c4.093,4.237,10.845,4.354,15.083,0.262c0.089-0.086,0.176-0.173,0.262-0.262l234.667-234.667c4.164-4.165,4.164-10.917,0-15.083L135.685,3.128z"/>
                    <path
                        d="M128.133,490.68c-5.891,0.011-10.675-4.757-10.686-10.648c-0.005-2.84,1.123-5.565,3.134-7.571l227.136-227.115L120.581,18.232c-4.171-4.171-4.171-10.933,0-15.104c4.171-4.171,10.933-4.171,15.104,0l234.667,234.667c4.164,4.165,4.164,10.917,0,15.083L135.685,487.544C133.685,489.551,130.967,490.68,128.133,490.68z"/>
                </svg>
            </li>
        </ul>
    );
};

export default Pagination;
