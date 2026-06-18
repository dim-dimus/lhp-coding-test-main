<?php

// Labelled versions of the city anchors used by database/seeders/EventSeeder.php.
// Events are seeded jittered +/-0.5 degrees around one of these points, so the
// nearest anchor reliably recovers the city. Used by App\Support\Geocoder to
// turn a latitude/longitude into a human-readable address offline (no API).

return [
    'anchors' => [
        // United States — [lat, lng, city, region, country]
        [40.7128, -74.0060, 'New York', 'NY', 'USA'],
        [34.0522, -118.2437, 'Los Angeles', 'CA', 'USA'],
        [41.8781, -87.6298, 'Chicago', 'IL', 'USA'],
        [29.7604, -95.3698, 'Houston', 'TX', 'USA'],
        [33.4484, -112.0740, 'Phoenix', 'AZ', 'USA'],
        [39.9526, -75.1652, 'Philadelphia', 'PA', 'USA'],
        [29.4241, -98.4936, 'San Antonio', 'TX', 'USA'],
        [32.7157, -117.1611, 'San Diego', 'CA', 'USA'],
        [32.7767, -96.7970, 'Dallas', 'TX', 'USA'],
        [37.3382, -121.8863, 'San Jose', 'CA', 'USA'],
        [30.2672, -97.7431, 'Austin', 'TX', 'USA'],
        [37.7749, -122.4194, 'San Francisco', 'CA', 'USA'],
        [47.6062, -122.3321, 'Seattle', 'WA', 'USA'],
        [39.7392, -104.9903, 'Denver', 'CO', 'USA'],
        [42.3601, -71.0589, 'Boston', 'MA', 'USA'],
        [36.1699, -115.1398, 'Las Vegas', 'NV', 'USA'],
        [25.7617, -80.1918, 'Miami', 'FL', 'USA'],
        [33.7490, -84.3880, 'Atlanta', 'GA', 'USA'],
        [38.9072, -77.0369, 'Washington', 'DC', 'USA'],
        [36.1627, -86.7816, 'Nashville', 'TN', 'USA'],
        [45.5152, -122.6784, 'Portland', 'OR', 'USA'],
        [29.9511, -90.0715, 'New Orleans', 'LA', 'USA'],

        // Canada
        [43.6532, -79.3832, 'Toronto', 'ON', 'Canada'],
        [45.5019, -73.5674, 'Montreal', 'QC', 'Canada'],
        [49.2827, -123.1207, 'Vancouver', 'BC', 'Canada'],
        [51.0447, -114.0719, 'Calgary', 'AB', 'Canada'],
        [45.4215, -75.6972, 'Ottawa', 'ON', 'Canada'],
        [53.5461, -113.4938, 'Edmonton', 'AB', 'Canada'],
        [46.8139, -71.2080, 'Quebec City', 'QC', 'Canada'],
        [49.8951, -97.1384, 'Winnipeg', 'MB', 'Canada'],

        // Mexico
        [19.4326, -99.1332, 'Mexico City', null, 'Mexico'],
        [20.6597, -103.3496, 'Guadalajara', null, 'Mexico'],
        [25.6866, -100.3161, 'Monterrey', null, 'Mexico'],
        [19.0414, -98.2063, 'Puebla', null, 'Mexico'],
        [32.5149, -117.0382, 'Tijuana', null, 'Mexico'],
        [21.1619, -86.8515, 'Cancún', null, 'Mexico'],
        [20.9674, -89.5926, 'Mérida', null, 'Mexico'],

        // Europe
        [51.5074, -0.1278, 'London', null, 'United Kingdom'],
        [48.8566, 2.3522, 'Paris', null, 'France'],
        [52.5200, 13.4050, 'Berlin', null, 'Germany'],
        [40.4168, -3.7038, 'Madrid', null, 'Spain'],
        [41.9028, 12.4964, 'Rome', null, 'Italy'],
        [52.3676, 4.9041, 'Amsterdam', null, 'Netherlands'],
        [41.3851, 2.1734, 'Barcelona', null, 'Spain'],
        [48.1351, 11.5820, 'Munich', null, 'Germany'],
        [45.4642, 9.1900, 'Milan', null, 'Italy'],
        [48.2082, 16.3738, 'Vienna', null, 'Austria'],
        [50.0755, 14.4378, 'Prague', null, 'Czechia'],
        [38.7223, -9.1393, 'Lisbon', null, 'Portugal'],
        [53.3498, -6.2603, 'Dublin', null, 'Ireland'],
        [55.6761, 12.5683, 'Copenhagen', null, 'Denmark'],
        [59.3293, 18.0686, 'Stockholm', null, 'Sweden'],
        [59.9139, 10.7522, 'Oslo', null, 'Norway'],
        [60.1699, 24.9384, 'Helsinki', null, 'Finland'],
        [50.8503, 4.3517, 'Brussels', null, 'Belgium'],
        [47.3769, 8.5417, 'Zurich', null, 'Switzerland'],
        [52.2297, 21.0122, 'Warsaw', null, 'Poland'],
        [47.4979, 19.0402, 'Budapest', null, 'Hungary'],
        [37.9838, 23.7275, 'Athens', null, 'Greece'],
        [45.7640, 4.8357, 'Lyon', null, 'France'],
        [53.5511, 9.9937, 'Hamburg', null, 'Germany'],
        [53.4808, -2.2426, 'Manchester', null, 'United Kingdom'],
        [55.9533, -3.1883, 'Edinburgh', null, 'United Kingdom'],
        [50.1109, 8.6821, 'Frankfurt', null, 'Germany'],
        [50.0647, 19.9450, 'Kraków', null, 'Poland'],
        [41.1579, -8.6291, 'Porto', null, 'Portugal'],
        [40.8518, 14.2681, 'Naples', null, 'Italy'],

        // Global hubs
        [35.6762, 139.6503, 'Tokyo', null, 'Japan'],
        [37.5665, 126.9780, 'Seoul', null, 'South Korea'],
        [1.3521, 103.8198, 'Singapore', null, 'Singapore'],
        [-33.8688, 151.2093, 'Sydney', null, 'Australia'],
        [-37.8136, 144.9631, 'Melbourne', null, 'Australia'],
        [25.2048, 55.2708, 'Dubai', null, 'UAE'],
        [-23.5505, -46.6333, 'São Paulo', null, 'Brazil'],
        [-34.6037, -58.3816, 'Buenos Aires', null, 'Argentina'],
    ],
];
