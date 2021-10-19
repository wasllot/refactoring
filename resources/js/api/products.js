import request from '@/utils/request';

export function fetchList(query) {
  return request({
    url: '/products',
    method: 'get',
    params: query,
  });
}

export function fetchProductByDateRange(data) {
  return request({
    url: '/products/' + data.date_start.getFullYear() + '-' + data.date_start.getMonth() + '-' + data.date_start.getDate() + '/' + data.date_end.getFullYear() + '-' + data.date_end.getMonth() + '-' + data.date_end.getDate(),
    method: 'get',
  });
}

export function metrics(query) {
  return request({
    url: '/metrics',
    method: 'get',
    params: query,
  });
}

export function fetchProduct(id) {
  return request({
    url: '/products/' + id,
    method: 'get',
  });
}

export function fetchPv(id) {
  return request({
    url: '/products/' + id + '/pageviews',
    method: 'get',
  });
}

export function createProduct(data) {
  return request({
    url: '/product/create',
    method: 'post',
    data,
  });
}

export function updateProduct(data) {
  return request({
    url: '/product/update',
    method: 'post',
    data,
  });
}
