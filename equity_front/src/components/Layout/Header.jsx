import "../styles/Header.css";

import { useState, useEffect } from "react";

export default function Header({ user, hasPermission, setSection }) {
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [darkMode, setDarkMode] = useState(true);

  useEffect(() => {
    if (darkMode) {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
  }, [darkMode]);

  const handleLogout = async () => {
    try {
      const token = localStorage.getItem("token");

      await fetch("http://localhost:8000/api/logout", {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      localStorage.removeItem("token");
      window.location.href = "/login";
    } catch (err) {
      console.error("Error en logout:", err);
    }
  };

  return (
    <header className="header">
      <div className="header-left">
        <button
          className="theme-toggle"
          onClick={() => setDarkMode((prev) => !prev)}
        >
          {darkMode ? "☀️" : "🌙"}
        </button>
      </div>

      <div className="header-center">
        <h1>Equity Link</h1>
      </div>

      <div className="header-right">
        <div
          className="user-circle"
          onClick={() => setDropdownOpen((prev) => !prev)}
        >
          {user.name[0]}
        </div>

        {dropdownOpen && (
          <div className="dropdown">
            {hasPermission("manage-users") && (
              <button onClick={() => setSection("users")}>
                Administración de Usuarios
              </button>
            )}
            {hasPermission("view-invoices") && (
              <button
                className="hide-big"
                onClick={() => setSection("invoices")}
              >
                Facturas
              </button>
            )}
            <button onClick={handleLogout}>Cerrar sesión</button>
          </div>
        )}
      </div>
    </header>
  );
}
