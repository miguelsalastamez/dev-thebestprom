import PrivateFolders from "./components/PrivateFolders/PrivateFolders";

const privateFoldersElement = document.getElementById('igd-private-folders-app');
if (privateFoldersElement) {
    ReactDOM.render(<PrivateFolders/>, privateFoldersElement);
}