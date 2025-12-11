import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_BACKEND_URL + '/api',
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
  baseURL: import.meta.env.VITE_BACKEND_URL, 
})

export default api;
export { webapi };