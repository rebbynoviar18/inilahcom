// ==============================================
// WEATHER WIDGET - COMPLETE JAVASCRIPT IMPLEMENTATION
// ==============================================

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const weatherWidget = document.getElementById('weatherWidget');
    const timeDisplay = document.getElementById('timeDisplay');
    const hoursDisplay = timeDisplay.querySelector('.hours');
    const minutesDisplay = timeDisplay.querySelector('.minutes');
    const secondsDisplay = timeDisplay.querySelector('.seconds');
    const dateDisplay = document.getElementById('dateDisplay');
    const temperature = document.getElementById('temperature');
    const weatherCondition = document.getElementById('weatherCondition');
    const locationDisplay = document.getElementById('location');
    const sun = document.getElementById('sun');
    const moon = document.getElementById('moon');
    const cloudContainer = document.getElementById('cloudContainer');
    const rainContainer = document.getElementById('rainContainer');
    const starContainer = document.getElementById('starContainer');

    // Configuration
    const API_KEY = '5d52453b2ff2aff68b6b4a5db6410967'; // Your OpenWeatherMap API key
    const CITY = 'Jakarta'; // Default city
    const UPDATE_INTERVAL = 30 * 60 * 1000; // 30 minutes in milliseconds

    // ======================
    // 1. CLOCK FUNCTIONALITY
    // ======================
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        // Smooth transitions for each time unit
        if (hours !== hoursDisplay.textContent) {
            animateTimeUnit(hoursDisplay, hours);
        }
        
        if (minutes !== minutesDisplay.textContent) {
            animateTimeUnit(minutesDisplay, minutes);
        }
        
        secondsDisplay.textContent = seconds;
        
        // Update date display
        updateDateDisplay(now);
        
        // Update time of day visuals
        updateTimeOfDay(now.getHours());
    }

    function animateTimeUnit(element, newValue) {
        element.style.transform = 'translateY(-10px)';
        element.style.opacity = '0';
        
        setTimeout(() => {
            element.textContent = newValue;
            element.style.transform = 'translateY(0)';
            element.style.opacity = '1';
        }, 150);
    }

    function updateDateDisplay(date) {
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        dateDisplay.textContent = date.toLocaleDateString('id-ID', options);
    }

    // ======================
    // 2. TIME OF DAY VISUALS
    // ======================
    function updateTimeOfDay(hour) {
        const timeOfDay = getTimeOfDay(hour);
        
        if (!weatherWidget.classList.contains(timeOfDay)) {
            updateDayNightVisuals(timeOfDay);
        }
    }

    function getTimeOfDay(hour) {
        if (hour >= 5 && hour < 11) return 'morning';
        if (hour >= 11 && hour < 15) return 'afternoon';
        if (hour >= 15 && hour < 18) return 'evening';
        return 'night';
    }

    function updateDayNightVisuals(timeOfDay) {
        // Update background class
        weatherWidget.classList.remove('morning', 'afternoon', 'evening', 'night');
        weatherWidget.classList.add(timeOfDay);
        
        // Toggle sun/moon with fade effect
        if (timeOfDay === 'night') {
            fadeOut(sun, () => {
                sun.style.display = 'none';
                moon.style.display = 'block';
                fadeIn(moon);
                createStars();
            });
        } else {
            fadeOut(moon, () => {
                moon.style.display = 'none';
                sun.style.display = 'block';
                fadeIn(sun);
                removeStars();
            });
        }
        
        // Adjust text color based on background
        adjustTextColor(timeOfDay);
    }

    function fadeOut(element, callback) {
        element.style.transition = 'opacity 0.3s ease';
        element.style.opacity = '0';
        setTimeout(callback, 300);
    }

    function fadeIn(element) {
        element.style.opacity = '0';
        setTimeout(() => {
            element.style.transition = 'opacity 0.3s ease';
            element.style.opacity = '1';
        }, 10);
    }

    function adjustTextColor(timeOfDay) {
        const isDarkBackground = ['night', 'rainy', 'cloudy'].includes(timeOfDay);
        const textColor = isDarkBackground ? '#ffffff' : '#000000';
        
        temperature.style.color = textColor;
        weatherCondition.style.color = textColor;
        locationDisplay.style.color = textColor;
    }

    // ======================
    // 3. WEATHER DATA & API
    // ======================
    async function getRealWeather() {
        try {
            const response = await fetch(
                `https://api.openweathermap.org/data/2.5/weather?q=${CITY}&appid=${API_KEY}&units=metric&lang=id`
            );
            const data = await response.json();
            
            if (data.cod === 200) {
                updateWeatherDisplay(data);
                updateWeatherAnimation(data.weather[0].main);
            }
        } catch (error) {
            console.error('Error fetching weather:', error);
            showFallbackWeather();
        }
    }

    function updateWeatherDisplay(data) {
        temperature.innerHTML = `${Math.round(data.main.temp)}<span class="degree">°C</span>`;
        weatherCondition.textContent = capitalizeFirstLetter(data.weather[0].description);
        locationDisplay.innerHTML = `<i class="fas fa-map-marker-alt" style="padding-right:0.3rem;"></i>${data.name}, ${data.sys.country}`;
    }

    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function showFallbackWeather() {
        temperature.innerHTML = `24<span class="degree">°C</span>`;
        weatherCondition.textContent = 'Cerah';
        locationDisplay.innerHTML = `<i class="fas fa-map-marker-alt" style="padding-right:0.3rem;"></i> ${CITY}, ID`;
    }

    // ======================
    // 4. WEATHER ANIMATIONS
    // ======================
    function updateWeatherAnimation(weatherMain) {
        // Reset all weather classes
        weatherWidget.classList.remove('clear', 'cloudy', 'rainy');
        
        switch(weatherMain.toLowerCase()) {
            case 'clear':
                weatherWidget.classList.add('clear');
                removeClouds();
                removeRain();
                break;
                
            case 'clouds':
                weatherWidget.classList.add('cloudy');
                createClouds();
                removeRain();
                break;
                
            case 'rain':
            case 'thunderstorm':
            case 'drizzle':
                weatherWidget.classList.add('rainy');
                removeClouds();
                createRain();
                break;
                
            default:
                weatherWidget.classList.add('clear');
        }
    }

    function createClouds() {
        removeClouds();
        
        const cloudCount = Math.floor(Math.random() * 3) + 3; // 3-5 clouds
        
        for (let i = 0; i < cloudCount; i++) {
            const cloud = document.createElement('div');
            cloud.className = 'cloud';
            
            // Random cloud properties
            const width = Math.random() * 120 + 80;
            const height = width * 0.6;
            const top = Math.random() * 60;
            const left = -width;
            const duration = Math.random() * 60 + 120;
            const delay = Math.random() * 20;
            const animationType = Math.random() > 0.5 ? 'cloudFloat' : 'cloudFloatSlow';
            
            // Apply styles
            cloud.style.cssText = `
                width: ${width}px;
                height: ${height}px;
                left: ${left}px;
                top: ${top}px;
                animation: ${animationType} ${duration}s linear ${delay}s infinite;
                opacity: ${Math.random() * 0.3 + 0.7};
            `;
            
            // Create cloud shape
            cloud.innerHTML = `
                <div style="position:absolute; width:${width*0.6}px; height:${height}px; background:white; border-radius:50%; top:0; left:0;"></div>
                <div style="position:absolute; width:${width*0.5}px; height:${height*0.8}px; background:white; border-radius:50%; top:${height*0.2}px; left:${width*0.4}px;"></div>
                <div style="position:absolute; width:${width*0.4}px; height:${height*0.6}px; background:white; border-radius:50%; top:${height*0.1}px; left:${width*0.7}px;"></div>
            `;
            
            cloudContainer.appendChild(cloud);
        }
    }

    function removeClouds() {
        while (cloudContainer.firstChild) {
            cloudContainer.removeChild(cloudContainer.firstChild);
        }
    }

    function createRain() {
        removeRain();
        rainContainer.style.display = 'block';
        
        const dropCount = Math.floor(Math.random() * 20) + 80; // 80-100 drops
        
        for (let i = 0; i < dropCount; i++) {
            const drop = document.createElement('div');
            drop.className = 'raindrop';
            
            // Random drop properties
            const left = Math.random() * 100;
            const delay = Math.random() * 2;
            const duration = Math.random() * 0.5 + 0.5;
            const length = Math.random() * 8 + 8;
            const opacity = Math.random() * 0.4 + 0.6;
            const angle = Math.random() * 20 - 10;
            
            // Apply styles
            drop.style.cssText = `
                left: ${left}%;
                height: ${length}px;
                animation: rainFall ${duration}s linear ${delay}s infinite;
                opacity: ${opacity};
                transform: rotate(${angle}deg);
            `;
            
            rainContainer.appendChild(drop);
        }
    }

    function removeRain() {
        rainContainer.style.display = 'none';
        while (rainContainer.firstChild) {
            rainContainer.removeChild(rainContainer.firstChild);
        }
    }

    function createStars() {
        removeStars();
        starContainer.style.display = 'block';
        
        const starCount = Math.floor(Math.random() * 50) + 100; // 100-150 stars
        
        for (let i = 0; i < starCount; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            
            // Random star properties
            const left = Math.random() * 100;
            const top = Math.random() * 100;
            const size = Math.random() * 3 + 1;
            const delay = Math.random() * 5;
            const duration = Math.random() * 3 + 2;
            const opacity = Math.random() * 0.5 + 0.5;
            
            // Apply styles
            star.style.cssText = `
                left: ${left}%;
                top: ${top}%;
                width: ${size}px;
                height: ${size}px;
                animation-duration: ${duration}s;
                animation-delay: ${delay}s;
                opacity: ${opacity};
            `;
            
            // Make some stars twinkle faster
            if (Math.random() > 0.7) {
                star.style.animationDuration = `${duration * 0.6}s`;
            }
            
            starContainer.appendChild(star);
        }
    }

    function removeStars() {
        starContainer.style.display = 'none';
        while (starContainer.firstChild) {
            starContainer.removeChild(starContainer.firstChild);
        }
    }

    // ======================
    // 5. INITIALIZATION
    // ======================
    function init() {
        // Start clock
        updateClock();
        setInterval(updateClock, 1000);
        
        // Load weather data
        getRealWeather();
        setInterval(getRealWeather, UPDATE_INTERVAL);
    }

    // Start the widget
    init();
});