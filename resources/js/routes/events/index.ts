import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import attendees from './attendees'
/**
* @see \App\Http\Controllers\EventController::index
* @see app/Http/Controllers/EventController.php:19
* @route '/events'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/events',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EventController::index
* @see app/Http/Controllers/EventController.php:19
* @route '/events'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EventController::index
* @see app/Http/Controllers/EventController.php:19
* @route '/events'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::index
* @see app/Http/Controllers/EventController.php:19
* @route '/events'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EventController::index
* @see app/Http/Controllers/EventController.php:19
* @route '/events'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::index
* @see app/Http/Controllers/EventController.php:19
* @route '/events'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::index
* @see app/Http/Controllers/EventController.php:19
* @route '/events'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\EventController::data
* @see app/Http/Controllers/EventController.php:40
* @route '/events/data'
*/
export const data = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: data.url(options),
    method: 'get',
})

data.definition = {
    methods: ["get","head"],
    url: '/events/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EventController::data
* @see app/Http/Controllers/EventController.php:40
* @route '/events/data'
*/
data.url = (options?: RouteQueryOptions) => {
    return data.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EventController::data
* @see app/Http/Controllers/EventController.php:40
* @route '/events/data'
*/
data.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: data.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::data
* @see app/Http/Controllers/EventController.php:40
* @route '/events/data'
*/
data.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: data.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EventController::data
* @see app/Http/Controllers/EventController.php:40
* @route '/events/data'
*/
const dataForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: data.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::data
* @see app/Http/Controllers/EventController.php:40
* @route '/events/data'
*/
dataForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: data.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::data
* @see app/Http/Controllers/EventController.php:40
* @route '/events/data'
*/
dataForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: data.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

data.form = dataForm

/**
* @see \App\Http\Controllers\EventController::show
* @see app/Http/Controllers/EventController.php:45
* @route '/events/{event}'
*/
export const show = (args: { event: string | { id: string } } | [event: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/events/{event}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EventController::show
* @see app/Http/Controllers/EventController.php:45
* @route '/events/{event}'
*/
show.url = (args: { event: string | { id: string } } | [event: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { event: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { event: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            event: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        event: typeof args.event === 'object'
        ? args.event.id
        : args.event,
    }

    return show.definition.url
            .replace('{event}', parsedArgs.event.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EventController::show
* @see app/Http/Controllers/EventController.php:45
* @route '/events/{event}'
*/
show.get = (args: { event: string | { id: string } } | [event: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::show
* @see app/Http/Controllers/EventController.php:45
* @route '/events/{event}'
*/
show.head = (args: { event: string | { id: string } } | [event: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EventController::show
* @see app/Http/Controllers/EventController.php:45
* @route '/events/{event}'
*/
const showForm = (args: { event: string | { id: string } } | [event: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::show
* @see app/Http/Controllers/EventController.php:45
* @route '/events/{event}'
*/
showForm.get = (args: { event: string | { id: string } } | [event: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::show
* @see app/Http/Controllers/EventController.php:45
* @route '/events/{event}'
*/
showForm.head = (args: { event: string | { id: string } } | [event: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\EventController::visual1
* @see app/Http/Controllers/EventController.php:30
* @route '/events-visual-1'
*/
export const visual1 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: visual1.url(options),
    method: 'get',
})

visual1.definition = {
    methods: ["get","head"],
    url: '/events-visual-1',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EventController::visual1
* @see app/Http/Controllers/EventController.php:30
* @route '/events-visual-1'
*/
visual1.url = (options?: RouteQueryOptions) => {
    return visual1.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EventController::visual1
* @see app/Http/Controllers/EventController.php:30
* @route '/events-visual-1'
*/
visual1.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: visual1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::visual1
* @see app/Http/Controllers/EventController.php:30
* @route '/events-visual-1'
*/
visual1.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: visual1.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EventController::visual1
* @see app/Http/Controllers/EventController.php:30
* @route '/events-visual-1'
*/
const visual1Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: visual1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::visual1
* @see app/Http/Controllers/EventController.php:30
* @route '/events-visual-1'
*/
visual1Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: visual1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::visual1
* @see app/Http/Controllers/EventController.php:30
* @route '/events-visual-1'
*/
visual1Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: visual1.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

visual1.form = visual1Form

/**
* @see \App\Http\Controllers\EventController::visual2
* @see app/Http/Controllers/EventController.php:35
* @route '/events-visual-2'
*/
export const visual2 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: visual2.url(options),
    method: 'get',
})

visual2.definition = {
    methods: ["get","head"],
    url: '/events-visual-2',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EventController::visual2
* @see app/Http/Controllers/EventController.php:35
* @route '/events-visual-2'
*/
visual2.url = (options?: RouteQueryOptions) => {
    return visual2.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EventController::visual2
* @see app/Http/Controllers/EventController.php:35
* @route '/events-visual-2'
*/
visual2.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: visual2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::visual2
* @see app/Http/Controllers/EventController.php:35
* @route '/events-visual-2'
*/
visual2.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: visual2.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EventController::visual2
* @see app/Http/Controllers/EventController.php:35
* @route '/events-visual-2'
*/
const visual2Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: visual2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::visual2
* @see app/Http/Controllers/EventController.php:35
* @route '/events-visual-2'
*/
visual2Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: visual2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EventController::visual2
* @see app/Http/Controllers/EventController.php:35
* @route '/events-visual-2'
*/
visual2Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: visual2.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

visual2.form = visual2Form

const events = {
    index: Object.assign(index, index),
    data: Object.assign(data, data),
    show: Object.assign(show, show),
    attendees: Object.assign(attendees, attendees),
    visual1: Object.assign(visual1, visual1),
    visual2: Object.assign(visual2, visual2),
}

export default events