// Simple script to test the trips API
const axios = require('axios');

const apiUrl = 'http://localhost:8000/api/public/trips';

async function testTripsApi() {
  try {
    console.log(`Testing API endpoint: ${apiUrl}`);
    const response = await axios.get(apiUrl);
    
    if (response.data && response.data.success) {
      console.log('API call successful!');
      console.log(`Found ${response.data.trips.length} trips`);
      
      if (response.data.trips.length > 0) {
        const firstTrip = response.data.trips[0];
        console.log('\nSample trip:');
        console.log(`ID: ${firstTrip.id}`);
        console.log(`From: ${firstTrip.origin} To: ${firstTrip.destination}`);
        console.log(`Traveler: ${firstTrip.traveler.name}`);
        console.log(`Departure: ${firstTrip.departure_date}`);
        console.log(`Available space: ${firstTrip.available_space} kg`);
      }
    } else {
      console.error('API call success but unexpected format:', response.data);
    }
  } catch (error) {
    console.error('Error testing trips API:', error.message);
    if (error.response) {
      console.error('Response status:', error.response.status);
      console.error('Response data:', error.response.data);
    }
  }
}

testTripsApi(); 