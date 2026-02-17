import axios from 'axios';

export const apiClient = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
    },
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('shop_api_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    const correlationId = crypto.randomUUID();
    config.headers['X-Correlation-Id'] = correlationId;

    return config;
});
