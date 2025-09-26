import { ReactNode, useEffect, useState } from 'react';
import { useAuth } from './AuthProvider';
import { Navigate, useSearchParams } from 'react-router-dom';
import { Loader2 } from 'lucide-react';

interface ProtectedRouteProps {
  children: ReactNode;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children }) => {
  const { user, verifySession, isLoading } = useAuth();
  const [searchParams] = useSearchParams();
  const [isChecking, setIsChecking] = useState(true);

  useEffect(() => {
    const checkAuth = async () => {
      if (user) {
        setIsChecking(false);
        return;
      }

      // Verificar se há parâmetros de sessão vindos do Klube Cash
      const sessionId = searchParams.get('session');
      const email = searchParams.get('email');
      const senat = searchParams.get('senat');

      if (sessionId && email && senat === 'Sim') {
        try {
          const verifiedUser = await verifySession(sessionId, email);
          if (verifiedUser) {
            setIsChecking(false);
            return;
          }
        } catch (error) {
          console.error('Erro ao verificar sessão:', error);
        }
      }

      setIsChecking(false);
    };

    checkAuth();
  }, [user, verifySession, searchParams]);

  if (isChecking || isLoading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="text-center space-y-4">
          <Loader2 className="mx-auto h-8 w-8 animate-spin text-blue-600" />
          <p className="text-gray-600">Verificando autenticação...</p>
        </div>
      </div>
    );
  }

  if (!user || user.senat !== 'Sim') {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
};

export default ProtectedRoute;