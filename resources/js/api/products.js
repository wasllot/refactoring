import request from '@/utils/request';

export function fetchList(query) {
  return request({
    url: '/products',
    method: 'get',
    params: query,
  });
}

export function fetchListByDate(date_start, date_end) {
  return request({
    url: '/products/' + date_start + '/' + date_end,
    method: 'get',
  });
}

export function paginateInStock(data) {
  return request({
    url: '/paginate_product_in_stock/' + data,
    method: 'get',
  });
}

export function fetchListInStock(query) {
  return request({
    url: '/products/in_stock',
    method: 'get',
    params: query,
  });
}

export function fetchListByDateInStock(date_start, date_end) {
  return request({
    url: '/products_in_stock/' + date_start + '/' + date_end,
    method: 'get',
  });
}

export function fetchFullListByDate(date_start, date_end, inStock) {
  return request({
    url: '/products_full_list/' + date_start + '/' + date_end + '/' + inStock,
    method: 'get',
  });
}

export function paginate(data) {
  return request({
    url: '/paginate_product/' + data,
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

export function fetchProductHistory(url) {
  return request({
    url: '/products/' + window.btoa(url),
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
