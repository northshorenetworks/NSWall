import axios from 'axios'

const api = axios.create({
  baseURL: '',
  headers: {
    'Content-Type': 'application/json'
  }
})

// Add API key if available
api.interceptors.request.use(config => {
  const apiKey = localStorage.getItem('nswall_api_key')
  if (apiKey) {
    config.headers['X-API-Key'] = apiKey
  }
  return config
})

// Handle errors
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Redirect to login or show auth modal
      console.error('Authentication required')
    }
    return Promise.reject(error)
  }
)

export default api
