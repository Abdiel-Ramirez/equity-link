export const isAdmin = (user) =>
  user.roles.some((r) => (typeof r === "string" ? r === "admin" : r.name === "admin"));

export const apiFetch = async (url, options = {}) => {
  const token = getToken();
  const headers = { Authorization: `Bearer ${token}`, ...(options.headers || {}) };

  const res = await fetch(url, { ...options, headers });
  const data = await res.json();
  if (!res.ok) throw new Error(data.message || "Error en la petición");
  return data.data;
};

export function getToken() {
  try {
    return localStorage.getItem("token") || null;
  } catch (e) {
    console.error("Error obteniendo token", e);
    return null;
  }
}
