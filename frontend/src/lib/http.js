import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost/test/limit-order-exchange-engine/backend/public/api', // change if needed
});

export function setAuthToken(token) {
  if (token) {
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
  } else {
    delete api.defaults.headers.common['Authorization'];
  }
}

// Load token from localStorage on startup
const token = localStorage.getItem('auth_token');
if (token) {
  setAuthToken(token);
}
const webapi = axios.create({
  baseURL: 'http://localhost/test/limit-order-exchange-engine/backend/public', // change if needed
})

export default api;
export { webapi };