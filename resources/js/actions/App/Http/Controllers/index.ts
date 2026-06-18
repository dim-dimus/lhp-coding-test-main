import EventController from './EventController'
import AttendeeController from './AttendeeController'
import Settings from './Settings'

const Controllers = {
    EventController: Object.assign(EventController, EventController),
    AttendeeController: Object.assign(AttendeeController, AttendeeController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers