import EventController from './EventController'
import Settings from './Settings'

const Controllers = {
    EventController: Object.assign(EventController, EventController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers