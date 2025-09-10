import { useState, useEffect } from "react";
import Swal from "sweetalert2";
import { apiFetch } from "../../utils/utils";

export default function InvoicesTable() {
  const [invoices, setInvoices] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(false);

  const fetchInvoices = async (page = 1) => {
    setLoading(true);
    try {
      const data = await apiFetch(
        `http://localhost:8000/api/invoices?page=${page}`
      );

      setInvoices(data.data);
      setPage(data.current_page);
      setLastPage(data.last_page);
    } catch (err) {
      console.error(err);
      Swal.fire(
        "Error",
        err.message || "No se pudieron cargar las facturas",
        "error"
      );
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInvoices(page);
  }, [page]);

  if (loading)
    return (
      <div className="card flex-grow">
        <p>Cargando facturas...</p>
      </div>
    );
  if (invoices.length === 0)
    return (
      <div className="card flex-grow">
        <p>No hay facturas registradas.</p>
      </div>
    );

  return (
    <div className="card flex-grow">
      <table className="brand-table">
        <thead>
          <tr>
            <th>UUID</th>
            <th>Folio</th>
            <th>Emisor</th>
            <th>Receptor</th>
            <th>Moneda</th>
            <th>Total</th>
            <th>Tipo de cambio</th>
          </tr>
        </thead>
        <tbody>
          {invoices.map((inv) => (
            <tr key={inv.id}>
              <td>{inv.uuid}</td>
              <td>{inv.folio || inv.uuid.slice(-6)}</td>
              <td>{inv.emisor}</td>
              <td>{inv.receptor}</td>
              <td>{inv.moneda}</td>
              <td>{inv.total}</td>
              <td>{inv.tipo_cambio || "-"}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <div className="pagination">
        <button
          onClick={() => setPage((p) => Math.max(p - 1, 1))}
          disabled={page === 1}
        >
          Anterior
        </button>
        <span>
          {page} / {lastPage}
        </span>
        <button
          onClick={() => setPage((p) => Math.min(p + 1, lastPage))}
          disabled={page === lastPage}
        >
          Siguiente
        </button>
      </div>
    </div>
  );
}
