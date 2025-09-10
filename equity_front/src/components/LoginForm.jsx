import "./styles/Login.css";
import { useState } from "react";
import Swal from "sweetalert2";

export default function LoginForm({ onLogin }) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email || !password) {
      Swal.fire("Error", "Por favor ingresa email y contraseña", "warning");
      return;
    }

    setLoading(true);
    try {
      const res = await fetch("http://localhost:8000/api/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });

      const data = await res.json();

      if (res.ok) {
        localStorage.setItem("token", data.data.access_token);
        onLogin();
      } else {
        Swal.fire("Error", data.message || "Login fallido", "error");
      }
    } catch (e) {
      console.error(e);
      Swal.fire("Error", "Error de conexión al servidor", "error");
    } finally {
      setLoading(false);
    }
  };

  return (
    <form className="login-card" onSubmit={handleSubmit}>
      <h2 className="login-title">Iniciar Sesión</h2>
      <p className="login-desc">Ingresa tu email y contraseña para continuar</p>

      <input
        type="email"
        placeholder="Email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        required
        className="login-input"
      />
      <input
        type="password"
        placeholder="Contraseña"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        required
        className="login-input"
      />

      <button type="submit" disabled={loading} className="login-button">
        {loading ? "Ingresando..." : "Login"}
      </button>
    </form>
  );
}
