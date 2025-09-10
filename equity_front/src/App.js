import { useState, useEffect } from "react";
import Swal from "sweetalert2";
import LoginForm from "./components/LoginForm";
import Dashboard from "./components/Dashboard";
import LoadingPage from "./components/LoadingPage";

export default function App() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchUser = async () => {
    setLoading(true);
    const token = localStorage.getItem("token") || null;

    try {
      const res = await fetch("http://localhost:8000/api/user", {
        credentials: "include",
        headers: { Authorization: `Bearer ${token}` },
      });

      if (!res.ok) {
        setUser(null);
      } else {
        const data = await res.json();
        setUser(data.data);
      }
    } catch (e) {
      console.error("Error fetching user", e);
      Swal.fire("Error", "Error de conexión al servidor", "error");
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUser();
  }, []);

  if (loading) return <LoadingPage/>;
  if (!user) return <LoginForm onLogin={fetchUser} />;
  return <Dashboard user={user} />;
}
