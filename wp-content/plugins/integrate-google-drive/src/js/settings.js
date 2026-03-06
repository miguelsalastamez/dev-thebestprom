import Settings from "./components/Settings";

const settingsElement = document.getElementById('igd-settings');
if (settingsElement) {
    ReactDOM.render(<Settings/>, settingsElement);
}