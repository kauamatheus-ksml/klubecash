import { useState, useEffect } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, UserCheck, ArrowLeft } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { useAuth } from "@/hooks/useAuth";

const Login = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { toast } = useToast();
  const { login, verifySession, isLoading, error } = useAuth();

  const [formData, setFormData] = useState({
    email: "",
    password: ""
  });

  // Verificar se há dados de sessão vindos do sistema principal
  useEffect(() => {
    const userSession = searchParams.get('session');
    const userEmail = searchParams.get('email');
    const userSenat = searchParams.get('senat');

    if (userSession && userEmail && userSenat === 'Sim') {
      // Tentar login automático com sessão do sistema principal
      handleAutoLogin(userSession, userEmail);
    }
  }, [searchParams]);

  const handleAutoLogin = async (session: string, email: string) => {
    try {
      const user = await verifySession(session, email);

      if (user) {
        toast({
          title: "Login realizado com sucesso!",
          description: `Bem-vindo ao SestSenat, ${user.nome}!`,
        });
        navigate('/');
      } else {
        toast({
          title: "Sessão inválida",
          description: "Não foi possível validar sua sessão. Faça login novamente.",
          variant: "destructive",
        });
      }
    } catch (error) {
      console.error('Erro no login automático:', error);
      toast({
        title: "Erro de conexão",
        description: "Não foi possível conectar com o servidor. Tente novamente.",
        variant: "destructive",
      });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      const user = await login(formData.email, formData.password);

      if (user.senat !== 'Sim') {
        toast({
          title: "Acesso negado",
          description: "Apenas usuários do Senat podem acessar este sistema.",
          variant: "destructive",
        });
        return;
      }

      toast({
        title: "Login realizado com sucesso!",
        description: `Bem-vindo ao SestSenat, ${user.nome}!`,
      });

      navigate('/');
    } catch (error) {
      toast({
        title: "Erro no login",
        description: error instanceof Error ? error.message : "Erro ao fazer login",
        variant: "destructive",
      });
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData(prev => ({
      ...prev,
      [e.target.name]: e.target.value
    }));
  };

  const goBackToKlubeCash = () => {
    window.location.href = 'https://klubecash.com/cliente/escolher-carteira';
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="w-full max-w-md space-y-6">
        {/* Header */}
        <div className="text-center space-y-2">
          <div className="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
            <UserCheck className="w-8 h-8 text-white" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900">SestSenat Portal</h1>
          <p className="text-gray-600">Acesso exclusivo para usuários do Senat</p>
        </div>

        {/* Login Form */}
        <Card>
          <CardHeader>
            <CardTitle>Entrar no Sistema</CardTitle>
            <CardDescription>
              Use suas credenciais do Klube Cash para acessar
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  name="email"
                  type="email"
                  placeholder="seu@email.com"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  disabled={isLoading}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Senha</Label>
                <Input
                  id="password"
                  name="password"
                  type="password"
                  placeholder="••••••••"
                  value={formData.password}
                  onChange={handleChange}
                  required
                  disabled={isLoading}
                />
              </div>

              {error && (
                <Alert variant="destructive">
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}

              <Button type="submit" className="w-full" disabled={isLoading}>
                {isLoading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Verificando...
                  </>
                ) : (
                  'Entrar'
                )}
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* Back to Klube Cash */}
        <div className="text-center">
          <Button
            variant="outline"
            onClick={goBackToKlubeCash}
            className="w-full"
          >
            <ArrowLeft className="mr-2 h-4 w-4" />
            Voltar para Klube Cash
          </Button>
        </div>

        {/* Info */}
        <div className="text-center text-sm text-gray-500">
          <p>Não tem acesso? Entre em contato com o suporte do Klube Cash</p>
        </div>
      </div>
    </div>
  );
};

export default Login;