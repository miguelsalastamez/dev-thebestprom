import ComputersPlaceholder from "./ComputersPlaceholder";
import SharedPlaceholder from "./SharedPlaceholder";
import StarredPlaceholder from "./StarredPlaceholder";
import SharedDrivesPlaceholder from "./SharedDrivesPlaceholder";

export default function Placeholder({activeFolder}) {
    const {id} = activeFolder;

    return (
        <>
            {'computers' === id && <ComputersPlaceholder/>}
            {'shared-drives' === id && <SharedDrivesPlaceholder/>}
            {'shared' === id && <SharedPlaceholder/>}
            {'starred' === id && <StarredPlaceholder/>}
        </>
    )
}